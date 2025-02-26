import PageObject from '../Page';

// https://developers.maya.ph/page/full-list-of-mock-cards

const CARDS = {
    VISA_3DS_FAIL_EXPIRED: {
        number: '4183590255919999',
        exp: '1218',
        cvv: '212',
        pass: '',
    },
    VISA_3DS_SUCCESS: {
        number: '4012001037141112',
        exp: '1227',
        cvv: '212',
        pass: '',
    },
    VISA_3DS_PASSWORD: {
        number: '4123450131001522',
        exp: '1225',
        cvv: '123',
        pass: 'mctest1',
    },
    VISA_SUCCESS: {
        number: '4012001038443335',
        exp: '1227',
        cvv: '774',
        pass: '',
    },
    VISA_INSUFFICIENT_FUNDS: {
        number: '4917610000000000',
        exp: '1225',
        cvv: '888',
        pass: 'paymaya88',
    },
    VISA_LOST_CARD: {
        number: '411111111117',
        exp: '1227',
        cvv: '111',
        pass: '',
    },
    VISA_STOLEN_CARD: {
        number: '411111111125',
        exp: '1227',
        cvv: '111',
        pass: '',
    },
    VISA_ACCOUNT_LIMIT_EXCEEDED: {
        number: '411111111133',
        exp: '1227',
        cvv: '111',
        pass: '',
    },
    MASTERCARD_CARD_EXPIRE: {
        number: '5424820003325881',
        exp: '0521',
        cvv: '346',
        pass: '',
    },
    MASTERCARD_SUCCESS: {
        number: '5123456789012346',
        exp: '1225',
        cvv: '111',
        pass: '',
    },
    MASTERCARD_3DS_PASSWORD: {
        number: '5453010000064154',
        exp: '1225',
        cvv: '111',
        pass: 'secbarry1',
    },
    MASTERCARD_INSUFFICIENT_FUNDS: {
        number: '5596459277363286',
        exp: '1225',
        cvv: '121',
        pass: 'paymaya12',
    },
    MASTERCARD_3DS_FAIL_EXPIRED: {
        number: '5424821930348582',
        exp: '0923',
        cvv: '313',
        pass: '',
    },
    MASTERCARD_LOST_CARD: {
        number: '511111111114',
        exp: '1227',
        cvv: '111',
        pass: '',
    },
    MASTERCARD_STOLEN_CARD: {
        number: '511111111122',
        exp: '1227',
        cvv: '111',
        pass: '',
    },
    MASTERCARD_ACCOUNT_LIMIT_EXCEEDED: {
        number: '511111111130',
        exp: '1227',
        cvv: '111',
        pass: '',
    },
    JCB_SUCCESS: {
        number: '3550998167531014',
        exp: '1225',
        cvv: '216',
        pass: '',
    },
    "JCB_3DS_PASSWORD": {
        number: '3550998167521049',
        exp: '1225',
        cvv: '995',
        pass: 'secure35',
    },
    JCB_LOST_CARD: {
        number: '355111111116',
        exp: '1227',
        cvv: '111',
        pass: '',
    },
    JCB_STOLEN_CARD: {
        number: '355111111124',
        exp: '1227',
        cvv: '111',
        pass: '',
    },
    JCB_ACCOUNT_LIMIT_EXCEEDED: {
        number: '355111111132',
        exp: '1227',
        cvv: '111',
        pass: '',
    },
    AMEX_3DS_SUCCESS: {
        number: '375987000169792',
        exp: '1227',
        cvv: '1111',
        pass: '',
    },
    AMEX_SUCCESS: {
        number: '370000000000002',
        exp: '1227',
        cvv: '9999',
        pass: '',
    },
    AMEX_LOST_CARD: {
        number: '371111111113',
        exp: '1227',
        cvv: '111',
        pass: '',
    },
    AMEX_STOLEN_CARD: {
        number: '371111111121',
        exp: '1227',
        cvv: '111',
        pass: '',
    },
    AMEX_ACCOUNT_LIMIT_EXCEEDED: {
        number: '371111111139',
        exp: '1227',
        cvv: '111',
        pass: '',
    },
} as const;

type Cards = keyof typeof CARDS;

class HostedCheckoutPage extends PageObject {
    async selectCard(cardType: Cards) {
        await this.page.getByLabel("Card Number").fill(CARDS[cardType].number);
        await this.page.getByLabel("Expiry Date").fill(CARDS[cardType].exp);
        await this.page.getByLabel("CVV").fill(CARDS[cardType].cvv);
    }

    async completeOrder() {
        await this.page.getByRole('button', { name: /complete order/i }).click();
    }

    async inputPassword(cardType: Cards, fail = false) {
        if (!CARDS[cardType].pass) {
            return;
        }

        if (fail) {
            await this.page.fill('#passkey', CARDS[cardType].pass! + '1');
        } else {
            await this.page.fill('#passkey', CARDS[cardType].pass!);
        }
        await this.page.getByRole('button', { name: 'Submit' }).click();
    }

}

export default HostedCheckoutPage;