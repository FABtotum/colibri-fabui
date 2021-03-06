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

__authors__ = "Krios Mane, Daniel Kesler"
__license__ = "GPL - https://opensource.org/licenses/GPL-3.0"
__version__ = "1.0"

# Import standard python module
import os, re
import urllib2
import hashlib

########################################################################
# Execute command via shell and return the complete output as a string
########################################################################
def shell_exec(cmd):
    stdin,stdout = os.popen2(cmd)
    stdin.close()
    lines = stdout.readlines(); 
    stdout.close()
    return lines

########################################################################
# Define on wich Raspberry im running on
########################################################################
def rpi_version():
    soc_id = shell_exec('</proc/cpuinfo grep Hardware | awk \'{print $3}\'')[0].strip()
    name_id = ''
    soc_name = {'BCM2708' : 'Raspberry Pi Model B', 'BCM2709' : 'Raspberry Pi 3 Model B' }
    if soc_id in soc_name:
        return soc_name[soc_id]
    else:
        return soc_id
########################################################################
# Define model depending on batch number
########################################################################
def fabtotum_model(batch_number):
    
    try:
        batch_number = int(batch_number)
    except:
        batch_number = 1
        
    model = ''
    
    if(batch_number >= 3000 and batch_number < 4000 ):
        model = 'FABtotum Hydra'
    elif(batch_number >= 2000 and batch_number < 3000):
        model = 'FABtotum CORE PRO'
    elif(batch_number >= 1000 and batch_number < 2000):
        model = "FABtotum Core"
    else:
        model = 'FABtotum Personal Fabricator'
    
    return model
########################################################################
# GET MAC ADDRESS
########################################################################
def get_mac_address(iface):
    result = shell_exec("ifconfig " + iface + " | grep -o -E '([[:xdigit:]]{1,2}:){5}[[:xdigit:]]{1,2}'")
    try:
        return result[0].strip()
    except IndexError:
        return "n.a"

########################################################################
# GET IP ADDRESS
########################################################################
def get_ip_address(iface):
    result = shell_exec("ifconfig " + iface + " | grep 'inet addr' | cut -d: -f2 | awk '{print $1}'")
    try:
        return result[0].strip()
    except IndexError:
        return "n.a"
########################################################################
# GET LOCAL BUNDLES LIST
########################################################################
def get_local_bundles():
    
    list = shell_exec("colibrimngr list") # get list
    list.pop(0) # remove first element "list"
    bundles = {}
    
    def get_group(index, regex):
        try:
            value = regex.group(index).strip()
        except IndexError:
            value = False
        return value
    
    for item in list:
        match = re.search('\[(\!*)(A|D|-|)\]\s(\d+)\s:(\s.*?\s):\s([0-9.]+)', item.strip(), re.IGNORECASE)
        if match != None:
            bundle_name = get_group(4, match)
            version     = get_group(5, match)
            state       = get_group(2, match)
            priority    = get_group(3, match)
            if(bundle_name):
                bundles[bundle_name] = {'name': bundle_name, 'version': version, 'state':state, 'priority': priority}  
    return bundles
########################################################################
# GET LOCAL BUNDLE INFO
########################################################################
def get_local_bundle(name):
    bundles = get_local_bundles()
    if(name in bundles):
        return bundles[name]
    else:
        return None
########################################################################
# GET DIRECTORY FREE SPACE IN BYTES
########################################################################
def get_dir_free_space(directory):
    result = shell_exec("df -Pk "+directory+" | tail -1 | awk '{print $4}'")
    try:
        kbytes = float(result[0].strip())
        return int(kbytes*1024)
    except IndexError:
        return "n.a"
########################################################################
# CLEAR BIGTEMP FOLDER  
########################################################################
def clear_big_temp(folder = '/mnt/bigtemp/'):
    shell_exec('sudo rm -rvf {0}/fabui/*.cb {1}/fabui/*.md5sum {2}/fabui/boot-*.zip {3}/fabui/fab_*.zip {4}/fabui/images'.format(folder, folder, folder, folder, folder))
        
########################################################################
# GET REMOTE FILE SIZE
########################################################################
def get_url_num_bytes(url):
    """
    Get number of bytes of file at a URL. None if not reported.
    """
    # urllib2 could be a bit more intelligent in guessing what I mean:
    if not re.match('^[a-zA-Z]*:', url):
        if os.path.exists(url):
            url = 'file:' + url
        else:
            url = 'http://' + url
    try:
        class HeadRequest(urllib2.Request):
            def get_method(self):
                return "HEAD"
        response = urllib2.urlopen(HeadRequest(
                url, None, {'User-Agent': 'Mozilla/5.0'}))
        # if using_py2:
        num_bytes = response.info().getheader('content-length')
        
    except Exception as e:
        print('Failed to connect to url: {0}'.format(url))
        num_bytes = None
    if num_bytes is not None:
        num_bytes = int(num_bytes)
    return num_bytes
########################################################################
# GET FILE MD5
########################################################################
def file_md5(file):
    hash_md5 = hashlib.md5()
    with open(file, "rb") as f:
        for chunk in iter(lambda: f.read(4096), b""):
            hash_md5.update(chunk)
    return hash_md5.hexdigest()