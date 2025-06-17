import PageObject from '../Page';

class ShopPage extends PageObject {
    async addToCart() {
        await this.page.goto('/shop/');
        const item = await this.page
			.locator('.woocommerce-loop-product__title')
			.first()
			.textContent();
		await this.page
			.getByRole('link', { name: /^Add.* to.* cart/i })
			.first()
			.click();
		await this.page
			.getByRole('link', { name: /^Add.* to.* cart/i })
			.first()
			.click();
		await this.page.getByRole('link', { name: 'View cart' }).click();

		return item;
    }
}

export default ShopPage;
