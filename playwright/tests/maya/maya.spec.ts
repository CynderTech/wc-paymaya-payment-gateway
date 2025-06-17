import { test, expect } from '@playwright/test';
import CartPage from '../../page_objects/paymongo/cart';
import CheckoutPage from '../../page_objects/paymongo/checkout';
import HostedCheckoutPage from '../../page_objects/maya/hostedCheckout';
import ShopPage from '../../page_objects/paymongo/shop';

let shopPage: ShopPage;
let cartPage: CartPage;
let checkoutPage: CheckoutPage;
let hostedCheckoutPage: HostedCheckoutPage;
let item: string | null;

test.beforeEach(async ({ page }) => {
	shopPage = new ShopPage(page);
	cartPage = new CartPage(page);
	checkoutPage = new CheckoutPage(page);
	hostedCheckoutPage = new HostedCheckoutPage(page);

	item = await shopPage.addToCart();
	await cartPage.proceedToCheckout();
	await checkoutPage.fillForm();
	await checkoutPage.selectPaymentMethod('maya');
	await checkoutPage.placeOrder();
});

test.describe('general', () => {
	test('the list should show up', async ({ page }) => {
		let itemRegex: RegExp | string;
		if (typeof item === 'string') {
			itemRegex = new RegExp(item, 'i');
		} else {
			itemRegex = '';
		}

		console.log(itemRegex);
		await hostedCheckoutPage.selectCard('VISA_3DS_FAIL_EXPIRED');
		await expect(page.getByText(/Order Summary/i)).toBeVisible();
		await expect(page.getByText(itemRegex)).toBeVisible();
	});
});

test.describe('visa', () => {
    test('3ds fail expire', async ({ page }) => {
        await hostedCheckoutPage.selectCard("VISA_3DS_FAIL_EXPIRED");
        await hostedCheckoutPage.completeOrder();

        await expect(page.getByText(/card is already expired/i)).toBeVisible();
    })

    test ('3ds success', async ({ page }) => {
        await hostedCheckoutPage.selectCard("VISA_3DS_SUCCESS");
        await hostedCheckoutPage.completeOrder();

        await expect(page.getByText(/Payment Successful/i)).toBeVisible();
        await page.getByRole('button', { name: /back to merchant/i }).click();
        await expect(page.getByText(/Thank You.* Order has been received/i)).toBeVisible();
    })

    test ('3ds password fail', async ({ page }) => {
        await hostedCheckoutPage.selectCard("VISA_3DS_PASSWORD");
        await hostedCheckoutPage.completeOrder();
        await hostedCheckoutPage.inputPassword("VISA_3DS_PASSWORD", true);

        await page.getByText(/back to merchant/i).click()
        await expect(page.getByText(/payment failed/i)).toBeVisible();
    })

    test ('3ds password success', async ({ page }) => {
        await hostedCheckoutPage.selectCard("VISA_3DS_PASSWORD");
        await hostedCheckoutPage.completeOrder();
        await hostedCheckoutPage.inputPassword("VISA_3DS_PASSWORD");

        await page.getByRole('button', { name: /back to merchant/i }).click()
        await expect(page.getByText(/Thank You.* Order has been received/i)).toBeVisible();
    })

    test ('success', async ({ page }) => {
        await hostedCheckoutPage.selectCard("VISA_SUCCESS");
        await hostedCheckoutPage.completeOrder();

        await page.getByRole('button', { name: /back to merchant/i }).click()
        await expect(page.getByText(/Thank You.* Order has been received/i)).toBeVisible();
    })

    test ('insufficient funds', async ({ page }) => {
        await hostedCheckoutPage.selectCard("VISA_INSUFFICIENT_FUNDS");
        await hostedCheckoutPage.completeOrder();
        await hostedCheckoutPage.inputPassword("VISA_INSUFFICIENT_FUNDS");

        await expect(page.getByText(/Payment Failed due to Insufficient balance/i)).toBeVisible();
        await page.getByText(/back to merchant/i).click()
        await expect(page.getByText(/payment failed/i)).toBeVisible();
    })

    test ('lost card', async ({ page }) => {
        await hostedCheckoutPage.selectCard("VISA_LOST_CARD");
        await hostedCheckoutPage.completeOrder();

        await expect(page.getByText(/Payment Failed/i)).toBeVisible();
        await page.getByText(/back to merchant/i).click()
        await expect(page.getByText(/payment failed/i)).toBeVisible();
    })

    test ('stolen card', async ({ page }) => {
        await hostedCheckoutPage.selectCard("VISA_STOLEN_CARD");
        await hostedCheckoutPage.completeOrder();

        await expect(page.getByText(/Payment Failed/i)).toBeVisible();
        await page.getByText(/back to merchant/i).click()
        await expect(page.getByText(/payment failed/i)).toBeVisible();
    })

    test ('account limit exceeded', async ({ page }) => {
        await hostedCheckoutPage.selectCard("VISA_ACCOUNT_LIMIT_EXCEEDED");
        await hostedCheckoutPage.completeOrder();

        await expect(page.getByText(/Issuer Decline.* Exceeded card limit/i)).toBeVisible();
        await page.getByText(/back to merchant/i).click();
        await expect(page.getByText(/payment failed/i)).toBeVisible();
    })
})

test.describe('mastercard', () => {
    test ('card expired', async ({ page }) => {
        await hostedCheckoutPage.selectCard("MASTERCARD_CARD_EXPIRE");
        await hostedCheckoutPage.completeOrder();
        await expect(page.getByText(/card is already expired/i)).toBeVisible();
    })

    test ('success', async ({ page }) => {
        await hostedCheckoutPage.selectCard("MASTERCARD_SUCCESS");
        await hostedCheckoutPage.completeOrder();
        await page.getByRole('button', { name: /back to merchant/i }).click()
        await expect(page.getByText(/Thank You.* Order has been received/i)).toBeVisible();
    })

    test ('3ds password fail', async ({ page }) => {
        await hostedCheckoutPage.selectCard("MASTERCARD_3DS_PASSWORD");
        await hostedCheckoutPage.completeOrder();
        await hostedCheckoutPage.inputPassword("MASTERCARD_3DS_PASSWORD", true);
        await page.getByText(/back to merchant/i).click()
        await expect(page.getByText(/payment failed/i)).toBeVisible();
    })

    test ('3ds password success', async ({ page }) => {
        await hostedCheckoutPage.selectCard("MASTERCARD_3DS_PASSWORD");
        await hostedCheckoutPage.completeOrder();
        await hostedCheckoutPage.inputPassword("MASTERCARD_3DS_PASSWORD");
        await page.getByRole('button', { name: /back to merchant/i }).click()
        await expect(page.getByText(/Thank You.* Order has been received/i)).toBeVisible();
    })

    test ('insufficient funds', async ({ page }) => {
        await hostedCheckoutPage.selectCard("MASTERCARD_INSUFFICIENT_FUNDS");
        await hostedCheckoutPage.completeOrder();
        await hostedCheckoutPage.inputPassword("MASTERCARD_INSUFFICIENT_FUNDS");

        await expect(page.getByText(/Payment Failed due to Insufficient balance/i)).toBeVisible();
        await page.getByText(/back to merchant/i).click()
        await expect(page.getByText(/payment failed/i)).toBeVisible();
    })

    test ('3ds fail expired', async ({ page }) => {
        await hostedCheckoutPage.selectCard("MASTERCARD_3DS_FAIL_EXPIRED");
        await hostedCheckoutPage.completeOrder();

        await expect(page.getByText(/card is already expired/i)).toBeVisible();
    })

    test ('lost card', async ({ page }) => {
        await hostedCheckoutPage.selectCard("MASTERCARD_LOST_CARD");
        await hostedCheckoutPage.completeOrder();

        await expect(page.getByText(/Payment Failed/i)).toBeVisible();
        await page.getByText(/back to merchant/i).click()
        await expect(page.getByText(/payment failed/i)).toBeVisible();
    })

    test ('stolen card', async ({ page }) => {
        await hostedCheckoutPage.selectCard("MASTERCARD_STOLEN_CARD");
        await hostedCheckoutPage.completeOrder();

        await expect(page.getByText(/Payment Failed/i)).toBeVisible();
        await page.getByText(/back to merchant/i).click()
        await expect(page.getByText(/payment failed/i)).toBeVisible();
    })

    test ('account limit exceeded', async ({ page }) => {
        await hostedCheckoutPage.selectCard("MASTERCARD_ACCOUNT_LIMIT_EXCEEDED");
        await hostedCheckoutPage.completeOrder();

        await expect(page.getByText(/Issuer Decline.* Exceeded card limit/i)).toBeVisible();
        await page.getByText(/back to merchant/i).click()
        await expect(page.getByText(/payment failed/i)).toBeVisible();
    })
})

test.describe('jcb', () => {
    test ('success', async ({ page }) => {
        await hostedCheckoutPage.selectCard("JCB_SUCCESS");
        await hostedCheckoutPage.completeOrder();

        await page.getByRole('button', { name: /back to merchant/i }).click()
        await expect(page.getByText(/Thank You.* Order has been received/i)).toBeVisible();
    })

    test ('3ds password fail', async ({ page }) => {
        await hostedCheckoutPage.selectCard("JCB_3DS_PASSWORD");
        await hostedCheckoutPage.completeOrder();
        await hostedCheckoutPage.inputPassword("JCB_3DS_PASSWORD", true);

        await page.getByText(/back to merchant/i).click()
        await expect(page.getByText(/payment failed/i)).toBeVisible();
    })

    test ('3ds password success', async ({ page }) => {
        await hostedCheckoutPage.selectCard("JCB_3DS_PASSWORD");
        await hostedCheckoutPage.completeOrder();
        await hostedCheckoutPage.inputPassword("JCB_3DS_PASSWORD");

        await page.getByRole('button', { name: /back to merchant/i }).click()
        await expect(page.getByText(/Thank You.* Order has been received/i)).toBeVisible();
    })

    test ('lost card', async ({ page }) => {
        await hostedCheckoutPage.selectCard("JCB_LOST_CARD");
        await hostedCheckoutPage.completeOrder();

        await expect(page.getByText(/Payment Failed/i)).toBeVisible();
        await page.getByText(/back to merchant/i).click()
        await expect(page.getByText(/payment failed/i)).toBeVisible();
    })

    test ('stolen card', async ({ page }) => {
        await hostedCheckoutPage.selectCard("JCB_STOLEN_CARD");
        await hostedCheckoutPage.completeOrder();
        
        await expect(page.getByText(/Payment Failed/i)).toBeVisible();
        await page.getByText(/back to merchant/i).click()
        await expect(page.getByText(/payment failed/i)).toBeVisible();
    })

    test ('account limit exceeded', async ({ page }) => {
        await hostedCheckoutPage.selectCard("JCB_ACCOUNT_LIMIT_EXCEEDED");
        await hostedCheckoutPage.completeOrder();

        await expect(page.getByText(/Issuer Decline.* Exceeded card limit/i)).toBeVisible();
        await page.getByText(/back to merchant/i).click()
        await expect(page.getByText(/payment failed/i)).toBeVisible();
    })
})

// test.describe('amex', () => {
//     test('3ds success', async ({ page }) => {
//         await hostedCheckoutPage.selectCard("AMEX_3DS_SUCCESS");
//         await hostedCheckoutPage.completeOrder();

//         await expect(page.getByText(/Payment Successful/i)).toBeVisible();
//         await page.getByRole('button', { name: /back to merchant/i }).click()
//         await expect(page.getByText(/Thank You.* Order has been received/i)).toBeVisible();
//     })

//     test ('success', async ({ page }) => {
//         await hostedCheckoutPage.selectCard("AMEX_SUCCESS");
//         await hostedCheckoutPage.completeOrder();

//         await page.getByRole('button', { name: /back to merchant/i }).click()
//         await expect(page.getByText(/Thank You.* Order has been received/i)).toBeVisible();
//     })

//     test ('lost card', async ({ page }) => {
//         await hostedCheckoutPage.selectCard("AMEX_LOST_CARD");
//         await hostedCheckoutPage.completeOrder();

//         await expect(page.getByText(/Payment Failed/i)).toBeVisible();
//         await page.getByText(/back to merchant/i).click()
//         await expect(page.getByText(/payment failed/i)).toBeVisible();
//     })

//     test ('stolen card', async ({ page }) => {
//         await hostedCheckoutPage.selectCard("AMEX_STOLEN_CARD");
//         await hostedCheckoutPage.completeOrder();

//         await expect(page.getByText(/Payment Failed/i)).toBeVisible();
//         await page.getByText(/back to merchant/i).click()
//         await expect(page.getByText(/payment failed/i)).toBeVisible();
//     })

//     test ('account limit exceeded', async ({ page }) => {
//         await hostedCheckoutPage.selectCard("AMEX_ACCOUNT_LIMIT_EXCEEDED");
//         await hostedCheckoutPage.completeOrder();

//         await expect(page.getByText(/Issuer Decline.* Exceeded card limit/i)).toBeVisible();
//         await page.getByText(/back to merchant/i).click()
//         await expect(page.getByText(/payment failed/i)).toBeVisible();
//     })
// })
