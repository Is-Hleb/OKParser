document.onreadystatechange = () => {
    const axios = require('axios');
    const updateitem = document.querySelector('#update');
    const loading = document.querySelector('#loading');

    loading.style["display"] = 'none';
    function update() {
        loading.style["display"] = 'block';
        console.log(loading.style)
        axios.get('/cron?js=true').then(r => {
            const html = r.data;
            updateitem.innerHTML = html;
            loading.style["display"] = 'none';
        })
    }
    document.querySelector('#update-btn').addEventListener('click', update);
}
