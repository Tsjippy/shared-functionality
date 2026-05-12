#pip install pynpm
#pip install PyGithub

import os
from pynpm import NPMPackage
from subprocess import Popen, PIPE

path = r"D:\LocalWebsites\sim-nigeria\app\public\wp-content\plugins"
file = 'CHANGELOG.md'

def scan_directory(p):
    for e in os.scandir(p):
        if e.is_dir() and 'tsjippy-' in e.name:
            update_js_css(e.path)

            # finds blocks folder
            for f in os.scandir(e.path):
                if f.is_dir() and 'blocks' in f.name:
                    # finds function folder
                    for b in os.scandir(f.path):
                        if b.is_dir():
                            update_js_css(b.path)

def update_js_css(p):
    print(p)
    js_folder = p
    script = 'build'

    if os.path.isfile(js_folder + '/js/package.json'):
        js_folder = js_folder + '/js'
        script = 'all'

    if os.path.isfile(js_folder + '/package.json'):
        # Rebuild js 
        pkg = NPMPackage(f'{js_folder}/package.json', shell=True)

        #update the version in package.json
        pkg.install()

        # Run all packages without waiting for the process to finish, so that we can run css build in parallel
        #pkg.run_script(script, wait=False)
        pkg.run_script(script)

    if os.path.isdir(p + '/css'):
        # Rebuild css
        for e in os.scandir(p + '/css'):
            if e.is_file() and '.scss' in e.name:
                process = Popen(['sass', '--style=compressed', e.path, e.path.replace('.scss', '.min.css')], stdout=PIPE, stderr=PIPE)
                stdout, stderr = process.communicate()

scan_directory(path)