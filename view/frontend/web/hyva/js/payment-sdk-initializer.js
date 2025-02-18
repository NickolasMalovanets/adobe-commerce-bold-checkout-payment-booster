export async function initializeBoldPayments(
    onRequireOrderDataCallback,
    onCreatePaymentOrderCallback,
    onUpdatePaymentOrderCallback,
    onApprovePaymentOrderCallback,
    onScaPaymentOrderCallback,
    onClickPaymentOrderCallback
) {
    if (!Alpine.store('bold')) {
        console.error('Bold store not initialized');
        return;
    }

    const loadPaymentSDK = async () => {
        const script = document.createElement('script');
        script.src = `${Alpine.store('bold').epsStaticUrl}/js/payments_sdk.js`;
        document.head.appendChild(script);
        await new Promise(resolve => script.onload = resolve);
    };

    const initPayments = async () => {
        const bold = Alpine.store('bold');
        window.boldPaymentsInstance = new window.bold.Payments({
            eps_url: bold.epsUrl,
            eps_bucket_url: bold.epsStaticUrl,
            group_label: bold.configurationGroupLabel,
            trace_id: bold.publicOrderId,
            payment_gateways: [{
                gateway_id: Number(bold.gatewayId),
                auth_token: bold.epsAuthToken,
                currency: bold.currency,
            }],
            callbacks: {
                onClickPaymentOrder: async (paymentType, paymentPayload) => {
                    return await onClickPaymentOrderCallback(paymentType, paymentPayload);
                },
                onCreatePaymentOrder: async (paymentType, paymentPayload) => {
                    return await onCreatePaymentOrderCallback(paymentType, paymentPayload);
                },
                onUpdatePaymentOrder: async (paymentType, paymentPayload) => {
                    return await onUpdatePaymentOrderCallback(paymentType, paymentPayload);
                },
                onApprovePaymentOrder: async (paymentType, paymentInformation, paymentPayload) => {
                    return await onApprovePaymentOrderCallback(paymentType, paymentInformation, paymentPayload);
                },
                onScaPaymentOrder: async (paymentType, paymentPayload) => {
                    return await onScaPaymentOrderCallback(paymentType, paymentPayload);
                },
                onRequireOrderData: async (requirements) => {
                    return onRequireOrderDataCallback(requirements);
                },
                onErrorPaymentOrder: async (errors) => {
                    console.error('An unexpected PayPal error occurred', errors);
                    hyvaCheckout.message.warn('Warning: An unexpected error occurred. Please try again.');
                }
            }
        });
        await window.boldPaymentsInstance.initialize;
    };
    await loadPaymentSDK();
    await initPayments();
}
