import PageObject from '../Page';

class CartPage extends PageObject {
    async proceedToCheckout() {
        await this.page.getByText(/Proceed to checkout/i).click();
    }
}

export default CartPage;