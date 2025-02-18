export default function FastlaneSdkInitializer() {
    if (!Alpine.store('bold')) {
        console.error('Bold store not initialized');
    }
}
