/*
** Zabbix
** Copyright (C) 2001-2023 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

package main

import (
	"errors"
	"flag"
	"fmt"
	"os"
	"reflect"
	"strings"
	"time"

	_ "zabbix.com/plugins"

	"git.zabbix.com/ap/plugin-support/conf"
	"git.zabbix.com/ap/plugin-support/log"
	"git.zabbix.com/ap/plugin-support/plugin/comms"
	"zabbix.com/internal/agent"
	"zabbix.com/internal/agent/keyaccess"
	"zabbix.com/internal/agent/remotecontrol"
	"zabbix.com/internal/agent/resultcache"
	"zabbix.com/internal/agent/scheduler"
	"zabbix.com/internal/agent/serverconnector"
	"zabbix.com/internal/agent/serverlistener"
	"zabbix.com/internal/agent/statuslistener"
	"zabbix.com/internal/monitor"
	"zabbix.com/pkg/pidfile"
	"zabbix.com/pkg/tls"
	"zabbix.com/pkg/version"
	"zabbix.com/pkg/zbxlib"
)

type AgentUserParamOption struct {
	UserParameter []string `conf:"optional"`
}

const remoteCommandSendingTimeout = time.Second

var manager *scheduler.Manager
var listeners []*serverlistener.ServerListener
var serverConnectors []*serverconnector.Connector
var closeChan = make(chan bool)
var stopChan = make(chan bool)

func processLoglevelIncreaseCommand(c *remotecontrol.Client) (err error) {
	if log.IncreaseLogLevel() {
		message := fmt.Sprintf("Increased log level to %s", log.Level())
		log.Infof(message)
		err = c.Reply(message)
		return
	}
	err = fmt.Errorf("Cannot increase log level above %s", log.Level())
	log.Infof(err.Error())

	return
}

func processLoglevelDecreaseCommand(c *remotecontrol.Client) (err error) {
	if log.DecreaseLogLevel() {
		message := fmt.Sprintf("Decreased log level to %s", log.Level())
		log.Infof(message)
		err = c.Reply(message)
		return
	}
	err = fmt.Errorf("Cannot decrease log level below %s", log.Level())
	log.Infof(err.Error())

	return
}

func processMetricsCommand(c *remotecontrol.Client) (err error) {
	data := manager.Query("metrics")
	return c.Reply(data)
}

func processVersionCommand(c *remotecontrol.Client) (err error) {
	data := version.Long()
	return c.Reply(data)
}

func processHelpCommand(c *remotecontrol.Client) (err error) {
	help := `Remote control interface, available commands:
	log_level_increase - Increase log level
	log_level_decrease - Decrease log level
	userparameter_reload - Reload user parameters
	metrics - List available metrics
	version - Display Agent version
	help - Display this help message`
	return c.Reply(help)
}

func processUserParamReloadCommand(c *remotecontrol.Client) (err error) {
	var userparams AgentUserParamOption

	if err = conf.LoadUserParams(&userparams); err != nil {
		err = fmt.Errorf("Cannot load user parameters: %s", err)
		log.Infof(err.Error())
		return
	}

	agent.Options.UserParameter = userparams.UserParameter

	if res := manager.QueryUserParams(); res != "ok" {
		err = fmt.Errorf("Failed to reload user parameters: %s", res)
		log.Infof(err.Error())
		return
	}

	message := "User parameters reloaded"
	log.Infof(message)
	err = c.Reply(message)

	return
}

func processRemoteCommand(c *remotecontrol.Client) (err error) {
	params := strings.Fields(c.Request())
	switch len(params) {
	case 0:
		return errors.New("Empty command")
	case 2:
		return errors.New("Too many commands")
	default:
	}

	switch params[0] {
	case "log_level_increase":
		err = processLoglevelIncreaseCommand(c)
	case "log_level_decrease":
		err = processLoglevelDecreaseCommand(c)
	case "help":
		err = processHelpCommand(c)
	case "metrics":
		err = processMetricsCommand(c)
	case "version":
		err = processVersionCommand(c)
	case "userparameter_reload":
		err = processUserParamReloadCommand(c)
	default:
		return errors.New("Unknown command")
	}
	return
}

var pidFile *pidfile.File

func run() (err error) {
	sigs := createSigsChan()

	var control *remotecontrol.Conn
	if control, err = remotecontrol.New(agent.Options.ControlSocket, remoteCommandSendingTimeout); err != nil {
		return
	}
	confirmService()
	control.Start()

loop:
	for {
		select {
		case sig := <-sigs:
			if !handleSig(sig) {
				break loop
			}
		case client := <-control.Client():
			if rerr := processRemoteCommand(client); rerr != nil {
				if rerr = client.Reply("error: " + rerr.Error()); rerr != nil {
					log.Warningf("cannot reply to remote command: %s", rerr)
				}
			}

			client.Close()
		case serviceStop := <-closeChan:
			if serviceStop {
				break loop
			}
		}
	}
	control.Stop()
	return nil
}

var (
	confDefault     string
	applicationName string
	pluginsocket    string

	argConfig  bool
	argTest    bool
	argPrint   bool
	argVersion bool
	argVerbose bool
)

type parameter struct {
	name string
	kind reflect.Kind
}

// This array defines the order in which command line flags are displayed in in help (usage) message to ensure that
// flags are displayed next to their shorthands. This is not guaranteed by the standard flag.Usage() function.
var params []parameter

// isZeroValue determines whether the string represents the zero value for a flag.
func isZeroValue(f *flag.Flag, value string) (ok bool, err error) {
	// Build a zero value of the flag's Value type, and see if the
	// result of calling its String method equals the value passed in.
	// This works unless the Value type is itself an interface type.
	typ := reflect.TypeOf(f.Value)
	var z reflect.Value
	if typ.Kind() == reflect.Pointer {
		z = reflect.New(typ.Elem())
	} else {
		z = reflect.Zero(typ)
	}
	// Catch panics calling the String method, which shouldn't prevent the
	// usage message from being printed, but that we should report to the
	// user so that they know to fix their code.
	defer func() {
		if e := recover(); e != nil {
			if typ.Kind() == reflect.Pointer {
				typ = typ.Elem()
			}
			err = fmt.Errorf("panic calling String method on zero %v for flag %s: %v", typ, f.Name, e)
		}
	}()
	return value == z.Interface().(flag.Value).String(), nil
}

// printOrderedHelp works similar to flag.PrintDefaults, but it prints usage of flags in the particular order defined by
// the global array params, not in lexicographical order.
func printOrderedHelp() {
	var isZeroValueErrs []error
	for _, param := range params {
		f := flag.Lookup(param.name)
		var b strings.Builder
		fmt.Fprintf(&b, "  -%s", f.Name) // Two spaces before -; see next two comments.
		name, usage := flag.UnquoteUsage(f)
		if len(name) > 0 {
			b.WriteString(" ")
			b.WriteString(name)
		}
		// Boolean flags of one ASCII letter are so common we
		// treat them specially, putting their usage on the same line.
		if b.Len() <= 4 { // space, space, '-', 'x'.
			b.WriteString("\t")
		} else {
			// Four spaces before the tab triggers good alignment
			// for both 4- and 8-space tab stops.
			b.WriteString("\n    \t")
		}
		b.WriteString(strings.ReplaceAll(usage, "\n", "\n    \t"))

		// Print the default value only if it differs to the zero value
		// for this flag type.
		if isZero, err := isZeroValue(f, f.DefValue); err != nil {
			isZeroValueErrs = append(isZeroValueErrs, err)
		} else if !isZero {
			if param.kind == reflect.String {
				// put quotes on the value
				fmt.Fprintf(&b, " (default %q)", f.DefValue)
			} else {
				fmt.Fprintf(&b, " (default %v)", f.DefValue)
			}
		}
		fmt.Fprint(flag.CommandLine.Output(), b.String(), "\n")
	}
	// If calling String on any zero flag.Values triggered a panic, print
	// the messages after the full set of defaults so that the programmer
	// knows to fix the panic.
	if errs := isZeroValueErrs; len(errs) > 0 {
		fmt.Fprintln(flag.CommandLine.Output())
		for _, err := range errs {
			fmt.Fprintln(flag.CommandLine.Output(), err)
		}
	}
}

func main() {
	version.Init(applicationName, tls.CopyrightMessage(), copyrightMessageMQTT(), copyrightMessageModbus())

	var confFlag string
	const (
		confDescription = "Path to the configuration file"
	)
	flag.StringVar(&confFlag, "config", confDefault, confDescription)
	flag.StringVar(&confFlag, "c", confDefault, confDescription+" (shorthand)")
	params = append(params, parameter{"c", reflect.String}, parameter{"config", reflect.String})

	var foregroundFlag bool
	const (
		foregroundDefault     = true
		foregroundDescription = "Run Zabbix agent in foreground"
	)
	flag.BoolVar(&foregroundFlag, "foreground", foregroundDefault, foregroundDescription)
	flag.BoolVar(&foregroundFlag, "f", foregroundDefault, foregroundDescription+" (shorthand)")
	params = append(params, parameter{"f", reflect.Bool}, parameter{"foreground", reflect.Bool})

	var testFlag string
	const (
		testDefault     = ""
		testDescription = "Test specified item and exit"
	)
	flag.StringVar(&testFlag, "test", testDefault, testDescription)
	flag.StringVar(&testFlag, "t", testDefault, testDescription+" (shorthand)")
	params = append(params, parameter{"t", reflect.String}, parameter{"test", reflect.String})

	var printFlag bool
	const (
		printDefault     = false
		printDescription = "Print known items and exit"
	)
	flag.BoolVar(&printFlag, "print", printDefault, printDescription)
	flag.BoolVar(&printFlag, "p", printDefault, printDescription+" (shorthand)")
	params = append(params, parameter{"p", reflect.Bool}, parameter{"print", reflect.Bool})

	var verboseFlag bool
	const (
		verboseDefault     = false
		verboseDescription = "Enable verbose output for metric testing or printing"
	)
	flag.BoolVar(&verboseFlag, "verbose", verboseDefault, verboseDescription)
	flag.BoolVar(&verboseFlag, "v", verboseDefault, verboseDescription+" (shorthand)")
	params = append(params, parameter{"v", reflect.Bool}, parameter{"verbose", reflect.Bool})

	var versionFlag bool
	const (
		versionDefault     = false
		versionDescription = "Print program version and exit"
	)
	flag.BoolVar(&versionFlag, "version", versionDefault, versionDescription)
	flag.BoolVar(&versionFlag, "V", versionDefault, versionDescription+" (shorthand)")
	params = append(params, parameter{"V", reflect.Bool}, parameter{"version", reflect.Bool})

	var remoteCommand string
	const remoteDefault = ""
	var remoteDescription = "Perform administrative functions (send 'help' for available commands) " +
		"(" + remoteCommandSendingTimeout.String() + " timeout)"
	flag.StringVar(&remoteCommand, "R", remoteDefault, remoteDescription)
	params = append(params, parameter{"R", reflect.String})

	var helpFlag bool
	const (
		helpDefault     = false
		helpDescription = "Display this help message"
	)
	flag.BoolVar(&helpFlag, "help", helpDefault, helpDescription)
	flag.BoolVar(&helpFlag, "h", helpDefault, helpDescription+" (shorthand)")
	params = append(params, parameter{"h", reflect.Bool}, parameter{"help", reflect.Bool})

	loadOSDependentFlags()
	flag.Parse()
	setServiceRun(foregroundFlag)

	if helpFlag {
		flag.Usage = func() {
			fmt.Fprintf(flag.CommandLine.Output(), "Usage of %s:\n", os.Args[0])
			printOrderedHelp()
		}
		flag.Usage()
		os.Exit(0)
	}

	var argConfig, argTest, argPrint, argVersion, argVerbose bool

	// Need to manually check if the flag was specified, as default flag package
	// does not offer automatic detection. Consider using third party package.
	flag.Visit(func(f *flag.Flag) {
		switch f.Name {
		case "c", "config":
			argConfig = true
		case "t", "test":
			argTest = true
		case "p", "print":
			argPrint = true
		case "V", "version":
			argVersion = true
		case "v", "verbose":
			argVerbose = true
		}
	})

	if argVersion {
		version.Display([]string{fmt.Sprintf("Plugin communication protocol version is %s", comms.ProtocolVersion)})
		os.Exit(0)
	}

	var err error
	if err = openEventLog(); err != nil {
		fatalExit("", err)
	}

	if err = validateExclusiveFlags(); err != nil {
		if eerr := eventLogErr(err); eerr != nil {
			err = fmt.Errorf("%s and %s", err, eerr)
		}
		fatalExit("", err)
	}

	if err = conf.Load(confFlag, &agent.Options); err != nil {
		if argConfig || !(argTest || argPrint) {
			if eerr := eventLogErr(err); eerr != nil {
				err = fmt.Errorf("%s and %s", err, eerr)
			}
			fatalExit("", err)
		}
		// create default configuration for testing options
		if !argConfig {
			_ = conf.Unmarshal([]byte{}, &agent.Options)
		}
	}

	if err = agent.ValidateOptions(&agent.Options); err != nil {
		if eerr := eventLogErr(err); eerr != nil {
			err = fmt.Errorf("%s and %s", err, eerr)
		}
		fatalExit("cannot validate configuration", err)
	}

	if err = handleWindowsService(confFlag); err != nil {
		if eerr := eventLogErr(err); eerr != nil {
			err = fmt.Errorf("%s and %s", err, eerr)
		}
		fatalExit("", err)
	}

	if err = log.Open(log.Console, log.Warning, "", 0); err != nil {
		fatalExit("cannot initialize logger", err)
	}

	if remoteCommand != "" {
		if agent.Options.ControlSocket == "" {
			log.Errf("Cannot send remote command: ControlSocket configuration parameter is not defined")
			os.Exit(0)
		}

		if reply, err := remotecontrol.SendCommand(agent.Options.ControlSocket, remoteCommand,
			remoteCommandSendingTimeout); err != nil {
			log.Errf("Cannot send remote command: %s", err)
		} else {
			log.Infof(reply)
		}
		os.Exit(0)
	}

	if pluginsocket, err = initExternalPlugins(&agent.Options); err != nil {
		fatalExit("cannot register plugins", err)
	}
	defer cleanUpExternal()

	if argTest || argPrint {
		var level int
		if argVerbose {
			level = log.Trace
		} else {
			level = log.None
		}
		if err = log.Open(log.Console, level, "", 0); err != nil {
			fatalExit("cannot initialize logger", err)
		}

		if err = keyaccess.LoadRules(agent.Options.AllowKey, agent.Options.DenyKey); err != nil {
			fatalExit("failed to load key access rules", err)
		}

		if _, err = agent.InitUserParameterPlugin(agent.Options.UserParameter, agent.Options.UnsafeUserParameters,
			agent.Options.UserParameterDir); err != nil {
			fatalExit("cannot initialize user parameters", err)
		}

		var m *scheduler.Manager
		if m, err = scheduler.NewManager(&agent.Options); err != nil {
			fatalExit("cannot create scheduling manager", err)
		}

		m.Start()

		if err = configUpdateItemParameters(m, &agent.Options); err != nil {
			fatalExit("cannot process configuration", err)
		}
		hostnames, err := agent.ValidateHostnames(agent.Options.Hostname)
		if err != nil {
			fatalExit("cannot parse the \"Hostname\" parameter", err)
		}
		agent.FirstHostname = hostnames[0]

		if err = configUpdateItemParameters(m, &agent.Options); err != nil {
			fatalExit("cannot process configuration", err)
		}

		agent.SetPerformTask(scheduler.Scheduler(m).PerformTask)

		if argTest {
			checkMetric(m, testFlag)
		} else {
			checkMetrics(m)
		}

		m.Stop()

		monitor.Wait(monitor.Scheduler)

		cleanUpExternal()

		os.Exit(0)
	}

	if argVerbose {
		fatalExit("", errors.New("verbose parameter can be specified only with test or print parameters"))
	}

	var logType, logLevel int
	switch agent.Options.LogType {
	case "system":
		logType = log.System
	case "console":
		logType = log.Console
	case "file":
		logType = log.File
	}
	switch agent.Options.DebugLevel {
	case 0:
		logLevel = log.Info
	case 1:
		logLevel = log.Crit
	case 2:
		logLevel = log.Err
	case 3:
		logLevel = log.Warning
	case 4:
		logLevel = log.Debug
	case 5:
		logLevel = log.Trace
	}

	if err = log.Open(logType, logLevel, agent.Options.LogFile, agent.Options.LogFileSize); err != nil {
		fatalExit("cannot initialize logger", err)
	}

	zbxlib.SetLogLevel(logLevel)

	greeting := fmt.Sprintf("Starting Zabbix Agent 2 (%s)", version.Long())
	log.Infof(greeting)

	addresses, err := serverconnector.ParseServerActive()
	if err != nil {
		fatalExit("cannot parse the \"ServerActive\" parameter", err)
	}

	if tlsConfig, err := agent.GetTLSConfig(&agent.Options); err != nil {
		fatalExit("cannot use encryption configuration", err)
	} else {
		if tlsConfig != nil {
			if err = tls.Init(tlsConfig); err != nil {
				fatalExit("cannot configure encryption", err)
			}
		}
	}

	if pidFile, err = pidfile.New(agent.Options.PidFile); err != nil {
		fatalExit("cannot initialize PID file", err)
	}
	defer pidFile.Delete()

	log.Infof("using configuration file: %s", confFlag)

	if err = keyaccess.LoadRules(agent.Options.AllowKey, agent.Options.DenyKey); err != nil {
		fatalExit("Failed to load key access rules", err)
		os.Exit(1)
	}

	if _, err = agent.InitUserParameterPlugin(agent.Options.UserParameter, agent.Options.UnsafeUserParameters,
		agent.Options.UserParameterDir); err != nil {
		fatalExit("cannot initialize user parameters", err)
	}

	if manager, err = scheduler.NewManager(&agent.Options); err != nil {
		fatalExit("cannot create scheduling manager", err)
	}

	// replacement of deprecated StartAgents
	if 0 != len(agent.Options.Server) {
		var listenIPs []string
		if listenIPs, err = serverlistener.ParseListenIP(&agent.Options); err != nil {
			fatalExit("cannot parse \"ListenIP\" parameter", err)
		}
		for i := 0; i < len(listenIPs); i++ {
			listener := serverlistener.New(i, manager, listenIPs[i], &agent.Options)
			listeners = append(listeners, listener)
		}
	}
	if err = loadOSDependentItems(); err != nil {
		fatalExit("cannot load os dependent items", err)
	}

	if foregroundFlag {
		if agent.Options.LogType != "console" {
			fmt.Println(greeting)
		}
	}

	manager.Start()

	if err = configUpdateItemParameters(manager, &agent.Options); err != nil {
		fatalExit("cannot process configuration", err)
	}

	hostnames, err := agent.ValidateHostnames(agent.Options.Hostname)
	if err != nil {
		fatalExit("cannot parse the \"Hostname\" parameter", err)
	}
	agent.FirstHostname = hostnames[0]
	hostmessage := fmt.Sprintf("Zabbix Agent2 hostname: [%s]", agent.Options.Hostname)
	log.Infof(hostmessage)
	if foregroundFlag {
		if agent.Options.LogType != "console" {
			fmt.Println(hostmessage)
		}
		fmt.Println("Press Ctrl+C to exit.")
	}
	if err = resultcache.Prepare(&agent.Options, addresses, hostnames); err != nil {
		fatalExit("cannot prepare result cache", err)
	}

	serverConnectors = make([]*serverconnector.Connector, len(addresses)*len(hostnames))

	var idx int
	for i := 0; i < len(addresses); i++ {
		for j := 0; j < len(hostnames); j++ {
			if serverConnectors[idx], err = serverconnector.New(manager, addresses[i], hostnames[j], &agent.Options); err != nil {
				fatalExit("cannot create server connector", err)
			}
			serverConnectors[idx].Start()
			agent.SetHostname(serverConnectors[idx].ClientID(), hostnames[j])
			idx++
		}
	}
	agent.SetPerformTask(manager.PerformTask)

	for _, listener := range listeners {
		if err = listener.Start(); err != nil {
			fatalExit("cannot start server listener", err)
		}
	}

	if agent.Options.StatusPort != 0 {
		if err = statuslistener.Start(manager, confFlag); err != nil {
			fatalExit("cannot start HTTP listener", err)
		}
	}

	if err == nil {
		err = run()
	}
	if err != nil {
		log.Errf("cannot start agent: %s", err.Error())
	}

	if agent.Options.StatusPort != 0 {
		statuslistener.Stop()
	}
	for _, listener := range listeners {
		listener.Stop()
	}
	for i := 0; i < len(serverConnectors); i++ {
		serverConnectors[i].StopConnector()
	}
	monitor.Wait(monitor.Input)

	manager.Stop()

	monitor.Wait(monitor.Scheduler)

	// split shutdown in two steps to ensure that result cache is still running while manager is
	// being stopped, because there might be pending exporters that could block if result cache
	// is stopped and its input channel is full.
	for i := 0; i < len(serverConnectors); i++ {
		serverConnectors[i].StopCache()
	}
	monitor.Wait(monitor.Output)
	farewell := fmt.Sprintf("Zabbix Agent 2 stopped. (%s)", version.Long())
	log.Infof(farewell)

	if foregroundFlag && agent.Options.LogType != "console" {
		fmt.Println(farewell)
	}

	waitServiceClose()
}

func fatalExit(message string, err error) {
	fatalCloseOSItems()

	if pluginsocket != "" {
		cleanUpExternal()
	}

	if len(message) == 0 {
		message = err.Error()
	} else {
		message = fmt.Sprintf("%s: %s", message, err.Error())
	}

	if agent.Options.LogType == "file" {
		log.Critf("%s", message)
	}

	fmt.Fprintf(os.Stderr, "zabbix_agent2 [%d]: ERROR: %s\n", os.Getpid(), message)
	os.Exit(1)
}
