const axios = require('axios');
console.info("Progress script loaded")

for (const task of TASKS) {
    queryStatus(task.id)
}

function updateProgress(progress, task_id) {
    progress = Math.min(Math.max(0, progress), 100);
    document.querySelector('#task-mask-' + task_id).style.width = progress + '%';
    console.log(progress, task_id);
}

function queryStatus(task_id) {
    axios.get('/parser/ui/task/export/stats/' + task_id).then(r => {
        setTimeout(() => {
            updateProgress(r.data, task_id)
            queryStatus(task_id)
        }, 1500);
    })
}

