console.log('logger js loaded');

var requestingLogs = false;
var id = -1;

document.addEventListener("DOMContentLoaded", async () => {
    // fetch all logs
    logUpdater();

    document.querySelector('.loader-wrapper').parentElement.remove();

    setInterval(logUpdater, 10000);
});

document.addEventListener("click", (event) => {
    let target = event.target;
    if(target.id == 'clear-logs'){
        let formData    = new FormData();
        formData.append('nonce', target.dataset.nonce);
        FormSubmit.fetchRestApi('clear_logs', formData);

        document.querySelector('.logs-wrapper').innerHTML = '';
    }else if(target.matches(`button.delete-message`)){
        let formData    = new FormData();
        formData.append('id', target.dataset.id);
        formData.append('nonce', target.dataset.nonce);

        FormSubmit.fetchRestApi('delete_log_entry', formData);

        target.closest(`.log-block`).remove();
    }else if(target.matches(`button.delete-similar`)){
        // Send delete request
        let formData    = new FormData();
        formData.append('id', target.dataset.id);
        formData.append('nonce', target.dataset.nonce);

        FormSubmit.fetchRestApi('delete_similar_log_entry', formData);

        // Remove all from screen
        let content = target.closest(`.log-block`).querySelector('i').textContent;

        document.querySelectorAll(`.log-block i`).forEach(el => {
            if(el.textContent == content){
                el.closest(`.log-block`).remove();
            }
        });
    }else if(target.matches(`button.ignore`)){
        // Send delete request
        let formData    = new FormData();
        formData.append('id', target.dataset.id);
        formData.append('nonce', target.dataset.nonce);

        FormSubmit.fetchRestApi('ignore_log_entry', formData);

        // Remove all from screen
        let content = target.closest(`.log-block`).querySelector('i').textContent;

        document.querySelectorAll(`.log-block i`).forEach(el => {
            if(el.textContent == content){
                el.closest(`.log-block`).remove();
            }
        });
    }
});

document.addEventListener('change', event => {
    if(event.target.name == 'log-level'){
        let logLevel = event.target.value;
        setLogLevelVisibility(logLevel);
    }
});

/**
 * Keep requesting 100 log entries untill there are none left
 * Then update the last retrieved id
 */
async function logUpdater(){
    let page = 0;
    while(true){
        let response = await updateLogs(page);

        page++;

        if(response == ''){
            break;
        }
    }

    id = response.last_id;
}


async function updateLogs(page=0){
    if(requestingLogs){
        return;
    }

    requestingLogs  = true;

    let wrapper     = document.querySelector('.logs-wrapper');

    let formData    = new FormData();
    formData.append('id', id);
    formData.append('page', page);
    formData.append('nonce', wrapper.dataset.nonce);

    let response = await FormSubmit.fetchRestApi('get_logs', formData);

    if(response){
        let logLevel    = document.querySelector(`[name="log-level"]:checked`).value;

        if(page == 0){
            wrapper.insertAdjacentHTML('afterbegin', response.html);
        }else{
            wrapper.insertAdjacentHTML('beforeend', response.html);
        }
        setLogLevelVisibility(logLevel);
    }

    requestingLogs  = false;

    return response;
};

function setLogLevelVisibility(logLevel){
    let logLevels   = [logLevel];

    if(logLevel == 'warning' || logLevel == 'info'){
        logLevels.push('error')
    }

    if(logLevel == 'info'){
        logLevels.push('warning')
    }

    document.querySelectorAll(`.log-block`).forEach(el => {
        if(logLevels.includes(el.dataset.level)){
            el.classList.remove('hidden');
        }else{
            el.classList.add('hidden');
        }
    });
}