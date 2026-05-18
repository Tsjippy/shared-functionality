console.log('logger js loaded');

document.addEventListener("DOMContentLoaded",() => {
    setInterval(updateLogs, 5000);
});

document.addEventListener("click", (event) => {
    if(event.target.id == 'clear-error-log'){
        FormSubmit.fetchRestApi('clear_error_log');

        document.querySelector('#debug-log .wrapper').innerHTML = '';
    }else if(event.target.id == 'clear-notice-log'){
        FormSubmit.fetchRestApi('clear_notice_log');

        document.querySelector('#notice-log .wrapper').innerHTML = '';
    }
});

async function updateLogs(){
    FormSubmit.fetchRestApi('get_error_log').then(response => {
        document.querySelector('#debug-log .wrapper').innerHTML   = response;
    });

    response = await FormSubmit.fetchRestApi('get_notice_log').then(response=>{
        document.querySelector('#notice-log .wrapper').innerHTML   = response;
    });
};