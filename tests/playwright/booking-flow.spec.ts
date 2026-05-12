import { test, expect } from '@playwright/test';

/**
 * End-to-end test: complete booking flow (Free plugin).
 *
 * Assumes wp-env is running at http://localhost:8888 with the plugin active,
 * one service created (ID 1), and one staff member assigned to that service.
 */

const BASE_URL = process.env.WP_BASE_URL ?? 'http://localhost:8888';
const BOOKING_PAGE = `${BASE_URL}/booking`;

test.describe('AppointKit Booking Flow', () => {

  test.beforeAll(async ({ request }) => {
    // Ensure there's a page with the [appointkit_form] shortcode at /booking.
    // This is set up by wp-env + the plugin fixtures.
  });

  test('completes a full booking (free service, no payment)', async ({ page }) => {
    await page.goto(BOOKING_PAGE);

    // Step 1: Select service.
    await expect(page.locator('.appointkit-booking-form')).toBeVisible();
    await expect(page.locator('.appointkit-service-list')).not.toContainText('Loading');

    const firstService = page.locator('.appointkit-service-card').first();
    await expect(firstService).toBeVisible();
    await firstService.click();

    // Step 2: Select staff.
    await expect(page.locator('.appointkit-step[data-step="2"]')).toBeVisible();
    const anyStaff = page.locator('.appointkit-staff-card').first();
    await expect(anyStaff).toBeVisible();
    await anyStaff.click();

    // Step 3: Select a date (tomorrow).
    await expect(page.locator('.appointkit-step[data-step="3"]')).toBeVisible();
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const dateStr = tomorrow.toISOString().split('T')[0];
    await page.fill('.appointkit-datepicker', dateStr);
    await page.keyboard.press('Enter');

    // Step 4: Select a time slot.
    await expect(page.locator('.appointkit-step[data-step="4"]')).toBeVisible();
    await expect(page.locator('.appointkit-slots')).not.toContainText('Loading');
    const firstSlot = page.locator('.appointkit-slot').first();
    await expect(firstSlot).toBeVisible();
    await firstSlot.click();

    // Step 5: Fill customer details.
    await expect(page.locator('.appointkit-step[data-step="5"]')).toBeVisible();
    await page.fill('#appointkit-name', 'E2E Test Customer');
    await page.fill('#appointkit-email', 'e2e@example.com');
    await page.fill('#appointkit-phone', '555-0100');

    // Navigate to step 6.
    await page.locator('.appointkit-btn--primary').click();

    // Step 6: Confirm booking (free service — no payment).
    await expect(page.locator('.appointkit-step[data-step="6"]')).toBeVisible();
    await expect(page.locator('.appointkit-summary')).toContainText('E2E Test Customer');

    await page.locator('#appointkit-submit').click();

    // Verify success state.
    await expect(page.locator('.appointkit-success')).toBeVisible({ timeout: 10000 });
    await expect(page.locator('.appointkit-success h3')).toHaveText('Booking Confirmed!');
  });

  test('shows no-slots message when no availability', async ({ page }) => {
    await page.goto(BOOKING_PAGE);

    // Skip to date step (requires staff with no availability on that date).
    // This test depends on a staff member with no availability set for the future.
    // Skipping if not applicable in current fixture.
    test.skip(true, 'Requires specific fixture: staff with no future availability');
  });

  test('form fields have accessible labels', async ({ page }) => {
    await page.goto(BOOKING_PAGE);

    // All form inputs should have labels.
    const inputs = await page.locator('input:visible, textarea:visible, select:visible').all();
    for (const input of inputs) {
      const id = await input.getAttribute('id');
      if (id) {
        const label = page.locator(`label[for="${id}"]`);
        const ariaLabel = await input.getAttribute('aria-label');
        const hasLabel = (await label.count()) > 0 || ariaLabel !== null;
        expect(hasLabel, `Input #${id} should have a label`).toBeTruthy();
      }
    }
  });

});
