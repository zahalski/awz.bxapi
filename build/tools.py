from chardet.universaldetector import UniversalDetector
import os
import shutil
import zipfile
import tempfile
import re
import json
import subprocess


def add_zip(arch, add_folder, mode, root_zip_folder=''):
    z = zipfile.ZipFile(arch, mode, zipfile.ZIP_DEFLATED, True)
    for root, dirs, files in os.walk(add_folder):
        for file in files:
            # Создание относительных путей и запись файлов в архив
            path = os.path.join(root, file)
            len_rm = len(add_folder)
            z.write(path, root_zip_folder+path[len_rm:])
    z.close()
    print('created zip', arch)


def encode_bx(filename, encoding_from='utf-8', encoding_to='windows-1251', original_file=''):
    with open(filename, 'r', encoding=encoding_from) as fr:
        with open(filename+'.tmp', 'w', encoding=encoding_to) as fw:
            for line in fr:
                fw.write(line)
    shutil.copyfile(filename+'.tmp', filename)
    os.remove(filename+'.tmp')
    print('converting ', original_file, 'from', encoding_from, 'to', encoding_to)


def check_encoding(file_path):
    detector = UniversalDetector()
    with open(file_path, 'rb') as fh:
        for line in fh:
            detector.feed(line)
            if detector.done:
                break
        detector.close()
    return {'charset': detector.result['encoding'], 'path': file_path}


def get_files(file_path, copy_dir):
    for name in os.listdir(file_path):
        if not os.path.isdir(os.path.join(file_path,name)):
            shutil.copyfile(os.path.join(file_path,name), os.path.join(copy_dir,name))
            if os.path.join('lang','ru') in os.path.join(file_path,name) or 'description.ru' in name:
                res = check_encoding(os.path.join(copy_dir,name))
                if res['charset'] != 'utf-8':
                    raise Exception('incorrect charset: '+res['charset']+' from file '+res['path'])
                else:
                    encode_bx(os.path.join(copy_dir,name), original_file=os.path.join(file_path,name))
        else:
            if not os.path.isdir(os.path.join(copy_dir,name)):
                os.mkdir(os.path.join(copy_dir,name))
            get_files(os.path.join(file_path,name), os.path.join(copy_dir,name))


def build_main(module_path, zip_name, folder=".last_version/"):
    version = get_module_version(module_path)
    if not version:
        raise Exception('is bitrix module? path: '+module_path)
    print('creating ', zip_name, 'module version', version)
    tmp_dir = tempfile.mkdtemp()
    get_files(module_path, tmp_dir)
    add_zip(zip_name, tmp_dir, "w", folder)
    shutil.rmtree(tmp_dir)


def get_module_version(module_path, encoding_file='utf-8'):
    version = False
    version_file = os.path.join(module_path, 'install/version.php')
    if not os.path.isfile(version_file):
        return version
    with open(version_file, 'r', encoding=encoding_file) as fv:
        for line in fv:
            if 'VERSION' in line and not 'VERSION_DATE' in line:
                try:
                    ob_re = re.search(re.compile("([0-9.]+)"), line)
                    version = ob_re.group(1)
                    if len(version) < 3:
                        version = False
                except Exception as e:
                    print(e)
                    version = False
    return version


def get_config():
    conf_file = 'conf.json'
    full_path = os.path.join('../', 'build', conf_file)
    if os.path.exists(full_path):
        with open(full_path, 'r') as file:
            json_data = json.load(file)
            require_key = [
                'module_path',
                'updates_path',
                'output_path',
                'lang_prefix',
                'git_path'
            ]
            for key in require_key:
                if not (key in json_data):
                    raise Exception(key+" is required key in "+conf_file)
            return json_data
    return False


def get_changed(updates_path, prepare_version):
    mark_path = os.path.join(updates_path, 'marked_hashes.json')
    json_data = {}
    if os.path.exists(mark_path):
        with open(mark_path, 'r') as file:
            json_data = json.load(file)
    if prepare_version in json_data:
        command = 'git diff --name-only '+json_data[prepare_version]
        run = subprocess.run(command, capture_output=True)
        return [str(path) for path in run.stdout.decode().strip().split("\n")]
    else:
        return []


def set_last_hash(updates_path, version):
    """
    запись хеша контрольной точки текущей версии
    для последующей проверки изменений файлов при билде следующей версии
    """
    mark_path = os.path.join(updates_path, 'marked_hashes.json')
    json_data = {}
    if os.path.exists(mark_path):
        with open(mark_path, 'r') as file:
            json_data = json.load(file)
    command = 'git rev-parse HEAD'
    run = subprocess.run(command, capture_output=True)
    json_start = json_data.copy()
    json_data[version] = run.stdout.decode().strip()
    with open(mark_path, "w") as outfile:
        json.dump(json_data, outfile)
    if version in json_start:
        if json_start[version] == json_data[version]:
            print("hash", json_data[version], "for version", version, "not updated", sep=" ")
        else:
            print("update hash", json_data[version], "for version", version, sep=" ")
    else:
        print("new hash", json_data[version], "for version", version, sep=" ")


def split_path(path, dirs=()):
    if path == '':
        return dirs
    temp_dir = os.path.split(path)
    if len(temp_dir) == 1:
        return dirs
    elif temp_dir[1] == '':
        return (temp_dir[0],)+dirs
    return split_path(temp_dir[0], temp_dir[1:]+dirs)