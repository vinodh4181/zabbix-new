//go:build windows
// +build windows

/*
** Zabbix
** Copyright (C) 2001-2022 Zabbix SIA
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

package registry

import (
	"encoding/json"
	"fmt"
	"strings"

	"golang.org/x/sys/windows/registry"
	"zabbix.com/pkg/plugin"
)

type Plugin struct{ plugin.Base }

var impl Plugin

type RegistryKeyValue struct {
	Name  string      `json:"{#VALUENAME}"`
	Value interface{} `json:"{#VALUE}"`
}

type RegistrySubKey struct {
	Name string `json:"{#SUBKEYNAME}"`
}

func getValue(key registry.Key, valueName string) (value interface{}, err error) {
	var buff []byte
	_, valueType, err := key.GetValue(valueName, buff)
	if valueType == registry.SZ || valueType == registry.EXPAND_SZ {
		value, _, err = key.GetStringValue(valueName)
	} else if valueType == registry.MULTI_SZ {
		var values []string
		values, _, err = key.GetStringsValue(valueName)
		value = strings.Join(values, "\n")
	} else if valueType == registry.DWORD || valueType == registry.QWORD {
		value, _, err = key.GetIntegerValue(valueName)
	} else if valueType == registry.BINARY {
		var values []byte
		values, _, err = key.GetBinaryValue(valueName)
		var hexValues []string
		for _, elem := range values {
			tmpHex := fmt.Sprintf("%02x", elem)
			hexValues = append(hexValues, tmpHex)
		}

		value = strings.Join(hexValues, " ")
	}
	return
}

func getKeyValue(key registry.Key, valueName string) (value interface{}, err error) {
	value, err = getValue(key, valueName)
	return
}

func getKeyMtime(key registry.Key) (value int64, err error) {
	keyInfo, err := key.Stat()
	value = keyInfo.ModTime().Unix()
	return
}

func getAllKeyValues(key registry.Key) (values []RegistryKeyValue, err error) {
	keyInfo, _ := key.Stat()
	valueNames, err := key.ReadValueNames(int(keyInfo.ValueCount))
	if err != nil {
		return
	}
	for _, valueName := range valueNames {
		keyValue, err := getValue(key, valueName)
		values = append(values, RegistryKeyValue{valueName, keyValue})
		if err != nil {
			break
		}
	}
	return
}

func getSubKeys(key registry.Key) (values []RegistrySubKey, err error) {
	keyInfo, _ := key.Stat()
	subKeys, err := key.ReadSubKeyNames(int(keyInfo.SubKeyCount))
	for _, subKey := range subKeys {
		values = append(values, RegistrySubKey{subKey})
	}
	return
}

func selectRootKey(keyName string) (key registry.Key) {
	keyMapping := map[string]registry.Key{
		"CLASSES_ROOT":     registry.CLASSES_ROOT,
		"CURRENT_USER":     registry.CURRENT_USER,
		"LOCAL_MACHINE":    registry.LOCAL_MACHINE,
		"USERS":            registry.USERS,
		"CURRENT_CONFIG":   registry.CURRENT_CONFIG,
		"PERFORMANCE_DATA": registry.PERFORMANCE_DATA,
	}
	key = keyMapping[keyName]
	return
}

func (p *Plugin) Export(key string, params []string, ctx plugin.ContextProvider) (result interface{}, err error) {
	keyPath := params[1]
	registryKeyName := selectRootKey(params[0])
	registryKey, err := registry.OpenKey(registryKeyName, keyPath, registry.QUERY_VALUE|registry.ENUMERATE_SUB_KEYS)
	defer registryKey.Close()
	switch key {
	case "registry.key.value":
		valueName := params[2]
		result, err = getKeyValue(registryKey, valueName)
	case "registry.key.get":
		var tmp_result []RegistryKeyValue
		tmp_result, err = getAllKeyValues(registryKey)
		result, _ = json.Marshal(tmp_result)
	case "registry.key.subkeys":
		var tmp_result []RegistrySubKey
		result, err = getSubKeys(registryKey)
		result, _ = json.Marshal(tmp_result)
	case "registry.key.mtime":
		result, err = getKeyMtime(registryKey)
	}

	return
}

func init() {
	plugin.RegisterMetrics(&impl, "WindowsRegistry",
		"registry.key.value", "Value of specified registrykey value name.",
		"registry.key.mtime", "Modification time of specified registry key",
		"registry.key.subkeys", "Lists subkeys of specified registry key",
		"registry.key.get", "Lists value names with their values of specified registry key",
	)
}
