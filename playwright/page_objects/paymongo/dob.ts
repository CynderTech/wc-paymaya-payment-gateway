import PageObject from '../Page';

const PIN_TYPE = {
    // Basic
    SUCCESSFUL_OTP: '111111',
    TRANSACTION_FAILED: '222222',
    OTP_REQUEST_FAILED: '333333',
    OTP_VERIFICATION_FAILED: '444444',
    INVALID_OTP: '000000',
    NC_PAID: '555555',
    NC_FAILED: '666666',
} as const;

type PinType = keyof typeof PIN_TYPE;

class DobPage extends PageObject {
    async pay() {
        await this.page.getByText(/Pay .*/i).click();
    }

    async inputPin(scenario: PinType) {
        await this.page.getByRole('textbox').fill(PIN_TYPE[scenario]);
        await this.page.getByRole('button', { name: /Submit OTP/i }).click();
    }

    async backToMerchant() {
        await this.page.getByRole('button', { name: /Back to merchant/i }).click();
    }
}

export default DobPage;