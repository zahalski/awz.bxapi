import sys
sys.path.append("../")
from build.tools import *

conf = get_config()
module_path = os.path.abspath(conf['module_path'])
updates_path = os.path.abspath(conf['updates_path'])
version = get_module_version(module_path)

if version:
    zip_name = os.path.abspath(conf['output_path'] + 'update/' + version + '.zip')
    updater_path = os.path.join(updates_path, version)
    build_main(updater_path, zip_name, version)
    set_last_hash(updates_path, version)