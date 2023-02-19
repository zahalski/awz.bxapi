import sys

sys.path.append("../")
from build.tools import *

conf = get_config()
updates_path = os.path.abspath(conf['updates_path'])
module_path = os.path.abspath(conf['module_path'])

change_log = {}
for name in os.listdir(updates_path):
    if name[-5:] == '.json':
        continue
    else:
        ver = str(name)
        ver_list = [f'{int(x):04}' for x in ver.split(".")]
        ver_key_str = '.'.join(ver_list)
        change_log[ver_key_str] = []

sorted_change_log = dict(sorted(change_log.items()))
last_version_ar = '.'.join([str(int(_)) for _ in sorted_change_log.popitem()[0].split('.')])
if len(sorted_change_log) == 0:
    last_version_updated = last_version_ar
else:
	last_version_updated = '.'.join([str(int(_)) for _ in sorted_change_log.popitem()[0].split('.')])

args = sys.argv
if len(args) > 1:
    last_version_updated = args[1]

files = get_changed(updates_path, last_version_updated)
last_version = get_module_version(module_path)

# добавление папки с обновлением
new_version_path = os.path.abspath(os.path.join(updates_path, last_version))
if not os.path.isdir(new_version_path):
    os.makedirs(new_version_path)
    print("created path", new_version_path)
else:
    raise Exception(new_version_path + ' is exists')

# добавление файла с описанием обновления
new_version_desc = os.path.join(new_version_path, 'description.ru')
if not os.path.isfile(new_version_desc):
    with open(new_version_desc, "w", encoding='utf-8') as outfile:
        outfile.write("- обновление " + last_version)
        print("created file", "description.ru")

# сколько папок в пути к модулю
module_paths = split_path(module_path)
# копирование измененных файлов
for file_updated_path in files:
    # источник
    file_path = os.path.abspath(os.path.join(conf['git_path'], file_updated_path))
    file_splited_path = split_path(file_path)
    file_copy_path = False
    # путь для копирования может быть только длинее
    if len(module_paths) < len(file_splited_path):
        if module_paths == file_splited_path[0:len(module_paths)]:
            file_copy_path = file_splited_path[len(module_paths):]
    # пустые директории игнорим
    if file_copy_path and os.path.isfile(file_path):
        # создаем папки, если их нет
        temp_path = new_version_path
        for dir in file_copy_path:
            temp_path = os.path.join(temp_path, dir)
            # проверка на конец пути (папка с именем файла не требуется)
            if os.path.isfile(file_path) and (file_splited_path[-1] == temp_path[-len(file_splited_path[-1]):]):
                continue
            if not os.path.isfile(temp_path) and not os.path.isdir(temp_path):
                os.makedirs(temp_path)
                print("created path", temp_path)
        dist_path = os.path.abspath(os.path.join(new_version_path, *file_copy_path))
        shutil.copy(file_path, dist_path)
        print("copied file", os.path.join(last_version, *file_copy_path))
