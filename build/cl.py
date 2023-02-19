import sys
sys.path.append("../")
from build.tools import *

conf = get_config()

change_log = {}
ver = '1000.0.0'
ver_list = [f'{int(x):04}' for x in ver.split(".")]
ver_key_str = '.'.join(ver_list)
change_log[ver_key_str] = []
change_log[ver_key_str].append('## История версий')

if not ("readme_file" in conf):
    raise Exception("README.MD file not found in conf.json")

updates_path = os.path.abspath(conf["updates_path"])
readme_file = os.path.abspath(conf["readme_file"])
for name in os.listdir(updates_path):
    if name[-5:] == '.json':
        continue
    with open(os.path.join(updates_path, name, 'description.ru'), 'r', encoding='utf-8') as fr:
        ver = str(name)
        ver_list = [f'{int(x):04}' for x in ver.split(".")]
        ver_key_str = '.'.join(ver_list)
        change_log[ver_key_str] = []
        change_log[ver_key_str].append('')
        change_log[ver_key_str].append('**version '+ver+'**    ')
        for line in fr:
            line_formated = line.strip()
            if line_formated[0] == '-' and line_formated[1] != ' ':
                line_formated = '- '+line_formated[1:]
            if line_formated[-1] == '.':
                line_formated = line_formated[:-1] + ';'
            elif line_formated[-1] != ';':
                line_formated = line_formated+';'
            change_log[ver_key_str].append(line_formated+'    ')
        if change_log[ver_key_str][-1][-5] == ';':
            change_log[ver_key_str][-1] = change_log[ver_key_str][-1][:-5]+'.    '

sorted_change_log = dict(sorted(change_log.items(), reverse = True))

all_rows = []
find_cl = False
with open(readme_file, 'r', encoding='utf-8') as fr:
    for line in fr:
        if not find_cl:
            all_rows.append(line)
            if '<!-- cl-start -->' in line:
                find_cl = True
                print("add change log after <!-- cl-start -->")
        else:
            if '<!-- cl-end -->' in line:
                for i in sorted_change_log:
                    lns = sorted_change_log[i]
                    for ln in lns:
                        all_rows.append(ln+"\n")
                all_rows.append(line)
                find_cl = False

with open(readme_file, 'w', encoding='utf-8') as fr:
    for wr in all_rows:
        fr.write(wr)