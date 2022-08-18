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

package win32

/*
#include <Windows.h>
#include <stdio.h>

void printExceptionInfo(EXCEPTION_POINTERS *ep)
{
	fprintf(stderr, "Unhandled exception %x detected at 0x%p. Printing go stack trace:\n\n",
			ep->ExceptionRecord->ExceptionCode, ep->ExceptionRecord->ExceptionAddress);
}
*/
import "C"

import (
	"os"
	"syscall"
	"runtime/debug"
	"fmt"

	"golang.org/x/sys/windows"
)

func printStackTrace(ep *C.struct__EXCEPTION_POINTERS) int {
	fmt.Fprintf(os.Stderr, "\n================================\n")

	C.printExceptionInfo(ep)
	debug.PrintStack()

	fmt.Fprintf(os.Stderr, "================================\n\n")

	return 0
}

func AddExceptionHandler() {
	addVectoredExceptionHandler := hKernel32.mustGetProcAddress("AddVectoredExceptionHandler")
	_, _, err := syscall.Syscall(addVectoredExceptionHandler, 2, 0, syscall.NewCallback(printStackTrace), 0)

	if err != windows.ERROR_SUCCESS {
		fmt.Fprintf(os.Stderr, "zabbix_agent2 [%d]: ERROR: cannot register windows exception handler: %s\n",
				os.Getpid(), err.Error())
	}
}
