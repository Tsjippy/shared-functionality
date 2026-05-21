console.log('logger js loaded');

document.addEventListener("DOMContentLoaded", async () => {
    await updateLogs(-1);

    document.querySelector('.loader-wrapper').parentElement.remove();

    setInterval(updateLogs, 10000);
});

document.addEventListener("click", (event) => {
    if(event.target.id == 'clear-logs'){
        FormSubmit.fetchRestApi('clear_logs');

        document.querySelector('.logs-wrapper').innerHTML = '';
    }
});

document.addEventListener('change', event => {
    if(event.target.name == 'log-level'){
        let logLevel = event.target.value;
        setLogLevelVisibility(logLevel);
    }
});

async function updateLogs(timestamp = null){
    if(timestamp == null){
        timestamp       = Date.now();
    }

    let formData    = new FormData();
    formData.append('timestamp', timestamp);

    let response = await FormSubmit.fetchRestApi('get_logs', formData);

    if(response){
        let logLevel    = document.querySelector(`[name="log-level"]:checked`).value;

        document.querySelector('.logs-wrapper').innerHTML     = response + document.querySelector('.logs-wrapper').innerHTML;
        setLogLevelVisibility(logLevel);
    }
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