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

# Import standard python module
import argparse
import time
import gettext

# Import external modules

# Import internal modules
from fabtotum.fabui.config  import ConfigService
from fabtotum.fabui.gpusher import GCodePusher
import fabtotum.fabui.macros.general as general_macros
import fabtotum.fabui.macros.printing as print_macros

# Set up message catalog access
tr = gettext.translation('mill', 'locale', fallback=True)
_ = tr.ugettext

################################################################################

class MillApplication(GCodePusher):
    """
    Milling application.
    """
    
    def __init__(self, log_trace, monitor_file, standalone = False, autolevel = False, finalize = True):
        super(MillApplication, self).__init__(log_trace, monitor_file, use_stdout=standalone )
        self.standalone = standalone
        self.autolevel = autolevel
        self.finalize = finalize
        
    def progress_callback(self, percentage):
        print "Progress", percentage
    
    def print_finalize(self):                                                                                                                                                                                                                                                                                                                                                                  
        if self.standalone or self.finalize:
            self.exec_macro("end_subtractive")
            
            if self.is_aborted():
                self.set_task_status(GCodePusher.TASK_ABORTED)
            else:
                self.set_task_status(GCodePusher.TASK_COMPLETED)
        
        self.stop()
    
    def first_move_callback(self):
        self.trace( _("Milling STARTED") )
        
        with self.monitor_lock:
            self.print_stats['first_move'] = True
            self.set_task_status(GCodePusher.TASK_RUNNING)
            self.update_monitor_file()

    def file_done_callback(self):   
        self.print_finalize()
        
    def state_change_callback(self, state):
        if state == 'paused':
            self.trace( _("Milling PAUSED") )
        if state == 'resumed':
            self.trace( _("Milling RESUMED") )
        if state == 'aborted':
            self.trace( _("Milling ABORTED") )
            self.file_done_callback()
        
    def run(self, task_id, gcode_file):
        """
        Run the print.
        
        :param gcode_file: GCode file containing print commands.
        :param task_id: Task ID
        :type gcode_file: string
        :type task_id: int
        """

        self.prepare_task(task_id, task_type='mill', gcode_file=gcode_file)
        self.set_task_status(GCodePusher.TASK_RUNNING)
        
        if self.standalone:
            #~ self.exec_macro("check_pre_print")
            self.exec_macro("start_subtractive")
        
        self.send_file(gcode_file)
        
        self.trace( _("Milling Initialized.") )

def main():
    config = ConfigService()

    # SETTING EXPECTED ARGUMENTS
    parser = argparse.ArgumentParser(formatter_class=argparse.ArgumentDefaultsHelpFormatter)
    parser.add_argument("task_id",      help=_("Task ID.") )
    parser.add_argument("file",         help=_("Gcode file to execute.") )
    parser.add_argument("--standalone", action='store_true',  help=_("Standalone operation. Does all preparations and cleanup.") )
    group = parser.add_argument_group( _('standalone arguments') )
#    group.add_argument("--autolevel",  action='store_true',  help=_("Auto bed leveling. Valid only when --standalone is used.") )

    # GET ARGUMENTS
    args = parser.parse_args()

    # INIT VARs
    gcode_file      = args.file         # GCODE FILE
    task_id         = args.task_id      # TASK ID  
    monitor_file    = config.get('general', 'task_monitor')      # TASK MONITOR FILE (write stats & task info, es: temperatures, speed, etc
    log_trace       = config.get('general', 'trace')        # TASK TRACE FILE 
    standalone      = args.standalone   # Standalong operation
    #~ autolevel       = args.autolevel    # Standalong operation
    autolevel       = False

    app = MillApplication(log_trace, monitor_file, standalone, autolevel)

    app.run(task_id, gcode_file)
    app.loop()

if __name__ == "__main__":
    main()
