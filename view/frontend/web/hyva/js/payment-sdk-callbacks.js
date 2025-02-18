export function onRequireOrderDataCallback(requirements) {
    const result = {};
    const cart = Alpine.store('bold').cart;
    for (const requirement of requirements) {
        console.log(requirement);
        result[requirement] = cart[requirement];
    }
    console.log(result);
    return result;
}

export async function onCreatePaymentOrderCallback(paymentType, paymentPayload) {
    if (paymentType !== 'ppcp') {
        return;
    }
    try {
        const expressPayOrderCreateResult = await fetch(
            `${Alpine.store('bold').shopUrl}/rest/V1/express_pay/order/create`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(
                    {
                        quoteMaskId: Alpine.store('bold').cart.id,
                        gatewayId: paymentPayload.gateway_id,
                        shippingStrategy: paymentPayload.shipping_strategy || 'dynamic'
                    }
                )
            }
        );
        return {
            payment_data: {
                id: (await expressPayOrderCreateResult.json())[0] || null
            }
        };
    } catch (e) {
        console.error(e);
        return {
            error: {
                message: 'Failed to create wallet payment order'
            }
        };
    }
}

export async function onUpdatePaymentOrderCallback(paymentType, paymentPayload) {
    console.log('onUpdatePaymentOrderCallback');
    console.log('Payment Type: ', paymentType);
    console.log('Payment Payload: ', paymentPayload);
}

export async function onApprovePaymentOrderCallback(paymentType, paymentInformation, paymentPayload) {
    console.log('onApprovePaymentOrderCallback');
    console.log('PaymentType: ', paymentType);
    console.log('PaymentInformation: ', paymentInformation);
    console.log('PaymentPayload: ', paymentPayload);
    hyvaCheckout.order.place().then(() => {
        console.log('Order placed');
    }).catch((e) => {
        hyvaCheckout.message.warn('Warning: An unexpected error occurred. Please try again.');
        console.error(e);
    });
}

export async function onScaPaymentOrderCallback(paymentType, paymentPayload) {
    console.log('onScaPaymentOrderCallback');
    console.log(paymentType);
    console.log(paymentPayload);
}

export function onClickPaymentOrderCallback(paymentType, paymentPayload) {
    console.log('onClickPaymentOrderCallback');
    console.log(paymentType);
    console.log(paymentPayload);
}
