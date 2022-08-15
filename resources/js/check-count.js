function fallbackCopyTextToClipboard(text) {
    var textArea = document.createElement("textarea");
    textArea.value = text;

    // Avoid scrolling to bottom
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";

    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        var successful = document.execCommand('copy');
        var msg = successful ? 'successful' : 'unsuccessful';
        console.log('Fallback: Copying text command was ' + msg);
    } catch (err) {
        console.error('Fallback: Oops, unable to copy', err);
    }

    document.body.removeChild(textArea);
}

function copyTextToClipboard(text) {
    if (!navigator.clipboard) {
        fallbackCopyTextToClipboard(text);
        return;
    }
    navigator.clipboard.writeText(text).then(function () {
        console.log('Async: Copying to clipboard was successful!');
    }, function (err) {
        console.error('Async: Could not copy text: ', err);
    });
}

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
                if (ev.target.checked) {
                    summ += count;
                } else {
                    summ -= count;
                }
                document.querySelector('#show-count').textContent = summ
                document.querySelector('#copy-count').onclick = () => {
                    copyTextToClipboard(summ.toString())
                    document.querySelector('#copy-count').classList.remove('btn-primary');
                    document.querySelector('#copy-count').classList.add('btn-success');
                    document.querySelector('#show-count').textContent = "Скопировано";

                    setTimeout(() => {
                        document.querySelector('#copy-count').classList.remove('btn-success');
                        document.querySelector('#copy-count').classList.add('btn-primary');
                        document.querySelector('#show-count').textContent = summ
                    }, 2000);

                }
            }
        })
    }

    // document.querySelector('#show-count').addEventListener('click', () => {
    //     alert("Колличество: " + summ);
    // })
}
