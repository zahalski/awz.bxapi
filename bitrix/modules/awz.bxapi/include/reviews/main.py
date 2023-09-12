import requests, requests.utils, pickle
import json
from bs4 import BeautifulSoup
from datetime import datetime
import time
import os
import hashlib
import sys

params = {}
if len(sys.argv) > 1:
    params["login"] = sys.argv[1]
    params["psw"] = sys.argv[2]

def get_path(file):
    cur_dir = os.path.realpath(os.path.dirname(__file__))
    return os.path.join(cur_dir, file)

def start_params():
    global params
    if not os.path.isfile(get_path('params.json')):
        with open('params.json', 'w', encoding='utf-8') as f:
            params['debug'] = True
            json.dump(params, f)
    with open(get_path('params.json'), 'r', encoding='utf-8') as f:
        prm = json.load(f)
        for key in prm:
            params[key] = prm[key]


def get_param(param_name, def_value=""):
    global params
    if not ('debug' in params):
        start_params()
    if param_name in params:
        return params[param_name]
    return def_value


def set_headers_start(session):
    session.headers.update({'User-Agent': get_param('user_agent')})
    return session


def get_session(max_time=86400):
    session_filename = get_path('session')
    if get_param('debug'):
        print('время жизни сессии:', max_time, 'сек.')
    if os.path.isfile(session_filename):
        if (time.time()-os.path.getctime(session_filename)) < max_time:
            if get_param('debug'):
                print('сессия загружена из файла,', 'до очистки: ', max_time - int(time.time()-os.path.getctime(session_filename)), 'сек.')
            with open(session_filename, 'rb') as f:
                session = pickle.load(f)
                return session
        else:
            if get_param('debug'):
                print('создание новой сессии')
            if os.path.isfile(session_filename):
                os.remove(session_filename)

    session = requests.Session()
    resp = session.get(get_param('aut_url'), headers = {
        'User-Agent': get_param('user_agent')
    })

    # Указываем referer. Иногда , если не указать , то приводит к ошибкам.
    session = set_headers_start(session)

    content = resp.text
    soup = BeautifulSoup(content, "html.parser")
    #form_key = soup.find('input', {'name': 'form_key'})['value']
    #session.cookies.set("form_key", form_key, domain=".vendors.bitrix24.ru")

    time.sleep(1)
    post_request = session.post(get_param('aut_url'), {
        'AUTH_FORM': 'Y',
        'TYPE': 'AUTH',
        'backurl': '/reviews/',
        'USER_LOGIN': get_param('login'),
        'USER_PASSWORD': get_param('psw'),
        'USER_REMEMBER': 'N',
        'Login': 'Войти',
    })

    if os.path.isfile(session_filename):
        os.chmod(session_filename, 0o755)
    with open(session_filename, 'wb') as f:
        pickle.dump(session, f)

    return session


def get_cache_key(cache_id, encoded=False):
    if encoded:
        return hashlib.sha256(cache_id).hexdigest()
    return hashlib.sha256(str(cache_id).encode('utf-8')).hexdigest()


def info_log(msg):
    now = datetime.now()
    dt_string = now.strftime("%d.%m.%Y %H:%M:%S")
    str_l = str(dt_string) + ': ' + msg
    print(str_l)


def get_page(session, url, headers, post_data=False, cache_time=86400):
    cache_id = get_cache_key(str(url).encode('utf-8')+str(json.dumps(post_data)).encode('utf-8'), True)
    if get_param('debug'):
        print('cache_id', cache_id)
    file_path = os.path.join(get_path('cache'), cache_id)
    if os.path.isfile(file_path):
        if (time.time()-os.path.getctime(file_path)) < cache_time:
            if get_param('debug'):
                print('контент загружен с кеша', 'до очистки: ', cache_time - int(time.time()-os.path.getctime(file_path)), 'сек.')
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
                info_log('страница получена из кеша ' + str(url))
                return content

    now = datetime.now()
    dt_string = now.strftime("%d.%m.%Y %H:%M:%S")
    if get_param('debug'):
        print(dt_string, 'get page', url, sep=', ')
    time.sleep(0.5)
    if post_data:
        prod_request = session.post(url, post_data, timeout=30, headers=headers)
    else:
        prod_request = session.get(url, timeout=30, headers=headers)
    content = prod_request.text
    if cache_time:
        if os.path.isfile(file_path):
            os.chmod(file_path, 0o755)
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(content)

    info_log('страница получена из сети ' + str(url))
    return content


def clear_file(file='res.json'):
    if os.path.isfile(get_path(file)):
        os.chmod(get_path(file), 0o755)
    with open(get_path(file), 'w', encoding='utf-8', newline='') as f:
        pass


def add_row(item, file='res.json'):
    with open(get_path(file), 'a', encoding='utf-8', newline='') as f:
        f.write(json.dumps(item, ensure_ascii=True, separators=(",", ":")))

headers = {
    'User-Agent': get_param('user_agent'),
    'Referer': get_param('aut_url'),
    'Accept' : 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7'
}
url_rew = 'https://vendors.bitrix24.ru/reviews/'
session = get_session(24*60)
content = get_page(session, url_rew, headers, False, 0)
if 'name="form_auth"' in content:
    session_filename = get_path('session')
    if os.path.isfile(session_filename):
        os.remove(session_filename)
    info_log("auth not found логин: "+get_param("login"))
    exit()


content = get_page(session, url_rew, headers, False, 0)
soup = BeautifulSoup(content, "html.parser")
items = []
for itm in soup.findAll('tr', class_='main-grid-row'):
    item = []
    for rev in itm.select('td.main-grid-cell .main-grid-cell-content'):
        item.append(rev.text)
    list_set = set(item)
    unique_list = (list(list_set))
    if len(unique_list) > 1:
        items.append(item)

clear_file()
add_row(items)