import { test, expect, Page } from "@playwright/test";

// HeroUI/react-aria's <Input> renders a second, name-less input alongside
// the real one for native autofill support — both match getByLabel(), so
// select by the actual form field name instead to avoid ambiguity.
async function fillLoginForm(page: Page, email: string, password: string) {
  await page.locator('input[name="email"]').fill(email);
  await page.locator('input[name="password"]').fill(password);
  await page
    .getByRole("main")
    .getByRole("button", { name: "Login", exact: true })
    .click();
}

test.describe("login", () => {
  test("redirects to the default page after a successful login", async ({
    page,
  }) => {
    await page.goto("/login");
    await fillLoginForm(page, "user@example.test", "correct-password");

    await expect(page).toHaveURL(/\/lab$/);
  });

  // Regression test for the open-redirect fix in
  // frontend/utils/safeRedirect.ts: a crafted `?redirect=` pointing at an
  // external site must never be followed after a real login.
  test("never redirects off-site even when ?redirect= points at an external URL", async ({
    page,
  }) => {
    await page.goto("/login?redirect=https://evil.example.test/phish");
    await fillLoginForm(page, "user@example.test", "correct-password");

    await expect(page).toHaveURL(/^http:\/\/127\.0\.0\.1:\d+\/lab$/);
    await expect(page).not.toHaveURL(/evil\.example\.test/);
  });

  test("also ignores a protocol-relative //evil.example redirect", async ({
    page,
  }) => {
    await page.goto("/login?redirect=%2F%2Fevil.example.test%2Fphish");
    await fillLoginForm(page, "user@example.test", "correct-password");

    await expect(page).toHaveURL(/^http:\/\/127\.0\.0\.1:\d+\/lab$/);
    await expect(page).not.toHaveURL(/evil\.example\.test/);
  });
});
