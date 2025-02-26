import PageObject from '../Page';

const CARD_PAYMENT_TYPE = {
    // Basic
    VISA: '4343434343434345',
    VISA_DEBIT: '4571736000000075',
    VISA_CREDIT_PH: '4009930000001421',
    VISA_DEBIT_PH: '4404520000001439',
    MASTERCARD: '5555444444444457',
    MASTERCARD_DEBIT: '5455590000000009',
    MASTERCARD_PREPAID: '5339080000000003',
    MASTERCARD_CREDIT_PH: '5240050000001440',
    MASTERCARD_DEBIT_PH: '5577510000001446',

    // 3D Secure Test Card Numbers
    // For use with PaymentIntents API
    // Cannot be used with Tokens API
    PAID_3DS: '4120000000000007',
    DECLINED_BEFORE_AUTH_3DS: '4230000000000004',
    DECLINED_AFTER_AUTH_3DS: '5234000000000106',
    PAID_AND_NOT_REQUIRED_3DS: '5123000000000001',

    // Specific Scenarios
    CARD_EXPIRED: '4200000000000018',
    CVC_INVALID: '4300000000000017',
    GENERIC_DECLINE: '4400000000000016',
    GENERIC_DECLINE_CREDIT_PH: '4028220000001457',
    FRAUDULENT: '4500000000000015',
    INSUFFICIENT_FUNDS: '5100000000000198',
    INSUFFICIENT_FUNDS_CREDIT_PH: '5240460000001466',
    PROCESSOR_BLOCKED: '5200000000000197',
    LOST_CARD: '5300000000000196',
    LOST_CARD_CREDIT_PH: '5483530000001462',
    STOLEN_CARD: '5400000000000195',
    PROCESSOR_UNAVAILABLE: '5500000000000194',
    BLOCKED: '4600000000000014',
    AWAITING_CAPTURE_NON_3DS: '5417881844647288',
    AWAITING_CAPTURE_3DS: '5417886761138807',
} as const;

type CardPaymentType = keyof typeof CARD_PAYMENT_TYPE;

const PAYMENT_METHODS = {
    creditCard: 'Credit Card',
    gcash: 'GCash',
    grabPay: 'GrabPay',
    payMaya: 'PayMaya',
    atome: 'Atome',
    bpi: 'BPI DOB',
    billease: 'BillEase',
    unionbank: 'UnionBank DOB',
    ccInstallment: 'Card Installment',
    giyapay: 'GiyaPay',
    maya: 'Maya',
    mayaPaymongo: 'Maya'
} as const;

type PaymentMethods = keyof typeof PAYMENT_METHODS;

class CheckoutPage extends PageObject {
    async fillForm() {
        await this.page.goto('/checkout/');
        await this.page.getByRole('textbox', { name: 'First name *' }).first().fill(process.env.WP_FNAME || 'Sample');
        await this.page.getByRole('textbox', { name: 'Last name *' }).first().fill(process.env.WP_LNAME || 'Sample');
        await this.page.getByRole('textbox', { name: 'Street address *' }).first().fill(process.env.STREET_ADDRESS || 'Sample');
        await this.page.getByRole('textbox', { name: 'Town / City *' }).first().fill(process.env.CITY || 'Sample');
        await this.page.getByRole('textbox', { name: 'Postcode / ZIP *' }).first().fill(process.env.ZIP || '1111');
        await this.page.getByLabel('Phone *').fill(process.env.PNUMBER || '999999999');
        await this.page.getByLabel('Email address *').fill(process.env.WP_EMAIL || 'sample@test.com');
    }

    async selectPaymentMethod(paymentMethod: PaymentMethods, cardPayment?: CardPaymentType) {
        if (paymentMethod === 'giyapay') {
            await this.page.getByText('GiyaPay Checkout Options').click();
        } else if (paymentMethod === 'maya') {
            await this.page.getByText('Payments via Maya', { exact: true }).click();
        } else {

            await this.page.getByText(`${PAYMENT_METHODS[paymentMethod]} via PayMongo`).click();

            if((paymentMethod === 'creditCard' || paymentMethod === 'ccInstallment') && cardPayment !== undefined) {
                let ccNoSelector = '';
                let expDateSelector = '';
                let cvvSelector = '';

                if(paymentMethod == 'creditCard') {
                    ccNoSelector = '#paymongo_ccNo';
                    expDateSelector = '#paymongo_expdate';
                    cvvSelector = '#paymongo_cvv';
                } else if (paymentMethod == 'ccInstallment') {
                    ccNoSelector = '#paymongo_cc_installment_ccNo';
                    expDateSelector = '#paymongo_cc_installment_expdate';
                    cvvSelector = '#paymongo_cc_installment_cvv';
                } else {
                    throw new Error('Only include a second argument when using the creditCard or ccInstallment payment method');
                }

                await this.page.waitForSelector('.paymongo-loading', { state: 'detached' });

                const year = (new Date).getFullYear().toString().slice(2);

                await this.page.locator(ccNoSelector).type(CARD_PAYMENT_TYPE[cardPayment]);
                await this.page.locator(expDateSelector).type('12' + year);
                await this.page.locator(cvvSelector).type('111');
            }
        }
    }

    async checkInstallmentsTermsAndConditions() {
        await this.page.getByLabel(/sample terms and conditions/i).check();
    }

    async placeOrder() {
        await this.page.getByRole('button', { name: /Place order/i }).click();
    }
}

export default CheckoutPage;
