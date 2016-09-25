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

__authors__ = "Marco Rizzuto, Daniel Kesler"
__license__ = "GPL - https://opensource.org/licenses/GPL-3.0"
__version__ = "1.0"


# Import standard python module
import gettext

# Import external modules

# Import internal modules


# Set up message catalog access
tr = gettext.translation('gmacro', 'locale', fallback=True)
_ = tr.ugettext

def extrude(app, args):
    filamentToExtrude = float(args[0])
    units_e = app.config.get('settings', 'e')
    app.macro("M92 E{0}".format(units_e), "ok", 2, _("Setting extruder mode"), 0)
    app.macro("M302",  "ok", 1,    _("Allowing cold extrusion"), 0)
    app.macro("G91",   "ok", 1,    _("Set rel position"), 0)
    app.macro("G0 E{0} F400".format(filamentToExtrude),    "ok", 100,    _("Extruding..."), 0)
    app.macro("M400",       "ok", 200,    _("Waiting for all moves to finish"), 1, verbose=False)
    
def change_step(app, args):
    new_step = float(args[0])
    app.macro("M92 E{0}".format(new_step),  "ok", 1,   _("Setting extruder mode"), 0)
    app.macro("M500",                       None, 1,   _("Writing settings to eeprom"), 0)

def pre_unload_spool(app, args = None):        
    app.macro("M104 S190",  "ok", 5,    _("Pre-Heating Nozzle..."), 0)
    app.macro("M109 S190",  None, 400,  _("Waiting for nozzle to reach temperature..."), 0) #heating and waiting.
    
def unload_spool(app, args = None):
    units_e = app.config.get('settings', 'e')
    
    app.trace( _("Unloading Spool : Procedure Started.") )
    app.macro("G90",                "ok", 10,   _("Set abs position"), 0, verbose=False)
    app.macro("M302 S0",            "ok", 10,   _("Extrusion prevention disabled"), 0.1,verbose=False)
    app.macro("G27",                "ok", 100,  _("Zeroing Z axis"), 1, verbose=False)
    app.macro("G0 Z150 F10000",     "ok", 10,   _("Moving to safe zone"), 0.1, verbose=False) #right top corner Z=150mm
    app.macro("G91",                "ok", 2,    _("Set rel position"), 0, verbose=False)
    app.macro("G92 E0",             "ok", 5,    _("Set extruder to zero"), 0.1, verbose=False)
    app.macro("M92 E{0}".format(units_e), "ok", 30,   _("Setting extruder mode"), 0.1, verbose=False)

    
    app.macro("M300",               "ok", 2,    _("<b>Start Pulling!</b>"), 0, verbose=False)
    app.macro("M400",               "ok", 100,  _("Wait for move to finish"), 0, verbose=False)
    app.trace( _("<b>Start Pulling!</b>") )
    app.macro("G0 E-80 F550",      "ok", 10,   _("Expelling filament"), 0)
    app.macro("M400",               "ok", 300,  _("Wait for move to finish"), 0, verbose=False)
    app.macro("G0 E-20 F550",      "ok", 10,   _("Expelling filament"), 0, verbose=False)
    app.macro("M400",               "ok", 300,  _("Wait for move to finish"), 0, verbose=False)
    
    app.macro("M104 S0",            "ok", 1,    _("Turning off heater"), 0.1)
    app.macro("M302 S170",          "ok", 10,   _("Extrusion prevention enabled"), 0.1, verbose=False)
    
def load_spool(app, args = None):
    units_e = app.config.get('settings', 'e')
    
    app.trace( _("Loading Spool : Procedure Started.") )
    app.macro("G90",                "ok", 2,    _("Set abs position"), 0, verbose=False)
    app.macro("G27",                "ok", 100,  _("Zeroing Z axis"), 0.1, verbose=False)
    app.macro("G0 Z150 F10000",     "ok", 10,   _("Moving to Safe Zone"), 0.1, verbose=False)
    app.macro("M302 S0",            "ok", 5,    _("Enabling Cold extrusion"), 0.1, verbose=False)
    app.macro("G91",                "ok", 2,    _("Set relative position"), 0, verbose=False)
    app.macro("G92 E0",             "ok", 5,    _("Setting extruder position to 0"), 0.1, verbose=False)
    app.macro("M92 E{0}".format(units_e), "ok", 5,    _("Setting extruder mode"), 0.1, verbose=False)
    app.macro("M104 S190",          "ok", 5,    _("Pre-Heating Nozzle. Get ready to push..."), 2) #heating and waiting.
    app.macro("M300",               "ok", 5,    _("<b>Start pushing!</b>"), 3)

    app.macro("G0 E110 F500",       "ok", 1,    _("Loading filament"), 15)
    app.macro("G0 E660 F700",       "ok", 1,    _("Loading filament (fast)"), 20)
    app.macro("M109 S210",          None, 400,  _("Waiting to get to temperature..."), 0.1) #heating and waiting.
    app.macro("M400",               "ok", 300,  _("Wait for move to finish"), 0, verbose=False)
    app.macro("G0 E100 F200",       "ok", 1,    _("Entering the hotend (slow)"), 0)
    app.macro("M400",               "ok", 300,  _("Wait for move to finish"), 0, verbose=False)

    app.macro("M104 S0",            "ok", 1,    _("Turning off heater"), 0.1)
    app.macro("M302 S170",          "ok", 1,    _("Disabling Cold Extrusion Prevention"), 0.1,verbose=False)
