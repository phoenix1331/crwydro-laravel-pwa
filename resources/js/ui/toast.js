export function toastStore() {
    return {
        message: null,
        tone: 'info',
        timer: null,

        show(message, tone = 'info', duration = 3500) {
            clearTimeout(this.timer);
            this.message = message;
            this.tone = tone;
            this.timer = setTimeout(() => (this.message = null), duration);
        },
    };
}
