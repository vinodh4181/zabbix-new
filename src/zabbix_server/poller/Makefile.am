## Process this file with automake to produce Makefile.in

noinst_LIBRARIES = libzbxpoller.a libzbxpoller_server.a libzbxpoller_proxy.a

libzbxpoller_a_SOURCES = \
	checks_agent.c \
	checks_calculated.c \
	checks_db.c \
	checks_external.c \
	checks_http.c \
	checks_internal.c \
	checks_java.c \
	checks_script.c \
	checks_simple.c \
	checks_simple_vmware.c \
	checks_snmp.c \
	checks_ssh.c \
	checks_telnet.c \
	poller.c \
	poller.h

libzbxpoller_server_a_SOURCES = \
	checks_internal_server.c

libzbxpoller_proxy_a_SOURCES = \
	checks_internal_proxy.c

libzbxpoller_a_CFLAGS = \
	-I$(top_srcdir)/src/libs/zbxsysinfo/simple \
	-I$(top_srcdir)/src/libs/zbxdbcache \
	$(SNMP_CFLAGS) \
	$(SSH2_CFLAGS) \
	$(SSH_CFLAGS)

libzbxpoller_server_a_CFLAGS = -I$(top_srcdir)/src/libs/zbxdbcache
