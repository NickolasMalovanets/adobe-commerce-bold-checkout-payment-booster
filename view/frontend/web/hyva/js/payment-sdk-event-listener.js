export function subscribeToSpiEvents() {
    window.addEventListener('message', async ({data}) => {
        const eventType = data?.eventType;
        if (eventType) {
            console.log('Event Type: ', eventType);
            console.log('Event Data: ', data);
        }
        switch (eventType) {
            case 'EVENT_SPI_PAYMENT_ORDER_SCA':
                Magewire.plugins.loader.stop();
                break;
            case 'EVENT_SPI_ENABLE_FULLSCREEN':
                Magewire.plugins.loader.stop();
                break;
            case 'EVENT_SPI_DISABLE_FULLSCREEN':
                Magewire.plugins.loader.start();
                break;
        }
    });
}
