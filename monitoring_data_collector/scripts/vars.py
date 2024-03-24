#!/usr/bin/env python
import sys
import subprocess as sp

def initialize(root_folder_path):

    global subpath
    global drupal_boostraped_command
    global drupal_version_command
    global drupal_version_number
    global move_to_root_path_command

    if '/web' in root_folder_path:
        move_to_root_path_command = 'cd {} && '.format(
            root_folder_path.replace('/web', ''))
        subpath='web'
        
    else:
        move_to_root_path_command = 'cd {} && '.format(
            root_folder_path.replace('/htdocs', ''))

    drupal_boostraped_command = sp.run('drush status | grep "Drupal bootstrap :"',  shell=True,
                                    check=True, stdout=sp.PIPE).stdout.decode('utf-8').replace("Drupal bootstrap : ", "")

    drupal_version_command = sp.run('drush status | grep "Drupal version   :"',  shell=True, check=True,
                                    stdout=sp.PIPE).stdout.decode('utf-8').replace("Drupal version   : ", "").split(".", 1)
    drupal_version_number = int(drupal_version_command[0].strip())