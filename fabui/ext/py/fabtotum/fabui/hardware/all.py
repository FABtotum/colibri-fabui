#!/bin/env python
# -*- coding: utf-8; -*-
#
# (c) 2016 FABtotum, http://www.fabtotum.com
#
# This file is part of FABUI.
#
# FABUI is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# FABUI is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with FABUI.  If not, see <http://www.gnu.org/licenses/>.

__authors__ = "Daniel Kesler, Krios Mane"
__license__ = "GPL - https://opensource.org/licenses/GPL-3.0"
__version__ = "1.0"

import fabtotum.fabui.hardware.general as general_hardware
import fabtotum.fabui.hardware.core    as core_hardware
import fabtotum.fabui.hardware.pro     as core_pro_hardware
import fabtotum.fabui.hardware.hydra   as hydra_hardware
import fabtotum.fabui.hardware.prism   as prism_hardware

PRESET_MAP = {
    "custom" : general_hardware.customHardware,
    "1"      : general_hardware.hardware1,
    "2"      : general_hardware.hardware2,
    "3"      : general_hardware.hardware3,
    "4"      : general_hardware.hardware4,
    "5"      : general_hardware.hardware5,
    
    #CORE
    "1000"   : core_hardware.hardware1000,
    
    #CORE PRO
    "2000"   : core_pro_hardware.hardware2000,
    "2100"   : core_pro_hardware.hardware2100,
    "2500"   : core_pro_hardware.hardware2500,
    "2550"   : core_pro_hardware.hardware2550,
    "2600"   : core_pro_hardware.hardware2600,

    #HYDRA
    "3000"   : hydra_hardware.hardware3000, 
    
    #PRISM
    "4000"   : prism_hardware.hardware4000
}
