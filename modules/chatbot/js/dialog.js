class Dialog {
    constructor(title) {
        this.dialog = document.createElement('div');
        this.dialog.className = 'dialog-overlay';
        this.dialog.innerHTML = `
            <div class="dialog">
                <div class="dialog-header">
                    <h3>${title}</h3>
                    <button class="close-btn" onclick="this.closest('.dialog-overlay').remove()">&times;</button>
                </div>
                <div class="dialog-content"></div>
                <div class="dialog-footer"></div>
            </div>
        `;
        document.body.appendChild(this.dialog);
    }

    setContent(content) {
        this.dialog.querySelector('.dialog-content').innerHTML = content;
    }

    addButton(label, callback) {
        const button = document.createElement('button');
        button.className = 'button';
        button.textContent = label;
        button.onclick = () => {
            const form = this.dialog.querySelector('form');
            if (form) {
                callback(form);
            } else {
                callback();
            }
        };
        this.dialog.querySelector('.dialog-footer').appendChild(button);
    }

    show() {
        this.dialog.style.display = 'flex';
    }

    hide() {
        this.dialog.style.display = 'none';
    }

    remove() {
        this.dialog.remove();
    }
} 