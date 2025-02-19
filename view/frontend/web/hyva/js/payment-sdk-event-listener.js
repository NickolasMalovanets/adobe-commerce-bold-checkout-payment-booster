export function subscribeToSpiEvents() {
    window.addEventListener('message', async ({data}) => {
        const eventType = data?.eventType;
        if (eventType) {
            console.log('Event Type: ', eventType);
            console.log('Event Data: ', data);
        }
        switch (eventType) {
            case 'EVENT_SPI_PAYMENT_ORDER_SCA':
                console.log(eventType);
                hyvaCheckout.loader.stop();
                break;
            case 'EVENT_SPI_ENABLE_FULLSCREEN':
                console.log(eventType);
                hyvaCheckout.loader.stop();
                break;
            case 'EVENT_SPI_DISABLE_FULLSCREEN':
                console.log(eventType);
                hyvaCheckout.loader.start();
                break;
        }
    });
}
