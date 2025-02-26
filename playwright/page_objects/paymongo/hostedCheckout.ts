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

type paymentName = 'creditCard' | 'gcash' | 'grabPay' | 'maya' | 'bpi' | 'unionBank' | 'billease';

type PaymentMethods = {
    [key in paymentName]: {
        name: string;
        type: 'Credit or Debit Card' | 'E-Wallets' | 'Online Banking' | 'Buy Now, Pay Later';
    };
};

const PAYMENT_METHODS: PaymentMethods = {
    creditCard: {
        name: 'Pay in full',
        type: 'Credit or Debit Card',
    },
    gcash: {
        name: 'GCash',
        type: 'E-Wallets',
    },
    grabPay: {
        name: 'GrabPay',
        type: 'E-Wallets',
    },
    maya: {
        name: 'Maya',
        type: 'E-Wallets',
    },
    bpi: {
        name: 'BPI Online',
        type: 'Online Banking',
    },
    unionBank: {
        name: 'UnionBank Online',
        type: 'Online Banking',
    },
    billease: {
        name: 'BillEase',
        type: 'Buy Now, Pay Later',
    },
};

class HostedCheckoutPage extends PageObject {
    async iFrameAuthorizePayment() {
        await this.page.frameLocator('iframe[title="3D Secure"]').getByRole('button', { name: 'Authorize Test Payment' }).click();
    }

    async authorizePayment() {
        await this.page.getByRole('button', { name: 'Authorize Test Payment' }).click();
    }

    async failPayment() {
        await this.page.getByRole('button', { name: 'Fail Test Payment' }).click();
    }

    async cancelPayment() {
        await this.page.getByRole('button', { name: 'Cancel Test Payment' }).click();
    }

    async selectPaymentMethod(paymentName: paymentName) {
        await this.page.getByRole('heading', { name: PAYMENT_METHODS[paymentName].type, exact: true }).click();
        await this.page.getByRole('menu').filter({ hasText: new RegExp(`^${PAYMENT_METHODS[paymentName].name}$`) }).click();

        await this.page.getByRole('button', { name: /continue/i }).click();
    }

    async insertCardInformation(cardPayment: CardPaymentType) {
        await this.page.waitForSelector('.paymongo-loading', { state: 'detached' });

        const year = (new Date).getFullYear().toString().slice(2);

        await this.page.locator('#cardNumber').type(CARD_PAYMENT_TYPE[cardPayment]);
        await this.page.getByPlaceholder('MM').type('12');
        await this.page.getByPlaceholder('YY').type(year);
        await this.page.getByPlaceholder('CVC').type('111');
    }

    async completePayment() {
        await this.page.getByRole('checkbox').check();

        await this.page.getByRole('button', { name: /complete payment/i }).click();
    }

    async goBack() {
        await this.page.getByRole('button', { name: /back/i }).click();
    }
}

export default HostedCheckoutPage;