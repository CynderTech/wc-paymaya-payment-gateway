import PageObject from '../Page';

class ShopPage extends PageObject {
    async addToCart() {
        await this.page.goto('/shop/');
        await this.page.getByRole('link', { name: /^Add.* to.* cart/i }).first().click();
        await this.page.getByRole('link', { name: /^Add.* to.* cart/i }).first().click();
        await this.page.getByRole('link', { name: 'View cart' }).click();
    }
}

export default ShopPage;
