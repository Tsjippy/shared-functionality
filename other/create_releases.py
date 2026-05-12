#pip install pynpm
#pip install PyGithub

import os
from pathlib import Path
import re
from pynpm import NPMPackage
from github import Github
from github import Auth
from github.GithubException import GithubException, UnknownObjectException

auth = Auth.Token("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")
github = Github(auth=auth)

path = r"D:\LocalWebsites\sim-nigeria\app\public\wp-content\plugins"
file = 'CHANGELOG.md'

def scan_directory(p):
    for e in os.scandir(p):
        if e.is_dir() and 'tsjippy-' in e.name:
            check_ready_for_release(e.path)

def check_ready_for_release(p):
    repoName = p.split('/')[-1].split('\\')[-1].split('tsjippy-')[-1]   
    repo = github.get_repo(f"tsjippy/{repoName}")

    latest_version = repo.get_latest_release().name
    splitted_version = latest_version.split('.')

    new_major = int(splitted_version[0])
    new_minor = int(splitted_version[1])
    new_last  = int(splitted_version[2]) + 1
    if new_last > 9:
        new_last    = 0
        new_minor   = new_minor + 1

        if new_minor > 9:
            new_minor = 0
            new_major = new_major + 1

    new_version = f"{new_major}.{new_minor}.{new_last}"

    # load changelog file
    changelog = Path(p+'\\'+file).read_text()

    # Get the whole unrelease section
    total       = re.search(r'## \[Unreleased\] - yyyy-mm-dd([\s\S]*?)## \[', changelog).group(1)
    newTotal    = total

    # Remove empty sections
    for x in ["Added", "Changed", "Fixed", "Updated"]:
        pattern = r'(### ' + x + r'[\s\S]*'

        if(x != 'Updated'):
            pattern = pattern + '?)###'
        else:
            pattern = pattern + ')'

        added   = re.search(pattern, total).group(1)

        if(added.rstrip("\n") == '### '+x):
            newTotal    = newTotal.replace(added, '')

    if total != newTotal and newTotal.replace("\n", "") != '':
        print(f"{repoName} is ready for release")
        print(newTotal)

        print(f"📝 Creating new release for {repoName}...")
        release = repo.create_git_release(new_version, new_version, newTotal)
        print(f"👌😎 Release {new_version} for {repoName} has been created !")
    else:
        print(f"{repoName} is not ready for release")



scan_directory(path)