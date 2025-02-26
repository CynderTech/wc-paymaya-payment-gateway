import { Page } from "@playwright/test";

class PageObject {
    page: Page

    constructor(page: Page) {
        this.page = page;
    }
}

export default PageObject;