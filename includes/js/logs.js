console.log('logger js loaded');

document.addEventListener("DOMContentLoaded",() => {
    updateLogs();
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

    const [errorLog, noticeLog] = await Promise.all([
        FormSubmit.fetchRestApi('get_error_log'),
        FormSubmit.fetchRestApi('get_notice_log')
    ]);

    document.querySelector('#debug-log .wrapper').innerHTML     = errorLog;
    document.querySelector('#notice-log .wrapper').innerHTML    = noticeLog;

    // call again
    updateLogs();
};