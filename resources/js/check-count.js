document.onreadystatechange = () => {
    const inputs = document.querySelectorAll(".check-count");

    let summ = 0;
    for (const input of inputs) {
        input.addEventListener('change', ev => {
            const itemId = ev.target.id.split('-')[1];
            const countItem = document.querySelector(`#count-${itemId}`);
            let count = 0;
            try {
                count = parseInt(countItem.textContent)
                if (!count) {
                    count = 0;
                }
            } catch (e) {
                count = 0;
            } finally {
                console.log(count);
                if(this.checked) {
                    summ += count;
                } else {
                    summ -= count;
                }
            }
        })
    }

    document.querySelector('#show-count').addEventListener('click', () => {
        alert("Колличество: " + summ);
    })
}
