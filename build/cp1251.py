import sys
sys.path.append("../")
from build.tools import *

conf = get_config()

module_path = os.path.abspath(conf["module_path"])
zip_name = os.path.abspath(conf["output_path"]+'.last_version.zip')

build_main(module_path, zip_name)
