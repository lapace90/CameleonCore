// tests/e2e/admin-reservation-flow.spec.js
import { test, expect } from '@playwright/test'
import { loginAsAdmin } from './utils/auth'

test.describe('Flux Admin - Gestion des Réservations', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsAdmin(page)
  })

  test('should create reservation via calendar', async ({ page }) => {
    await page.goto('/admin/calendar')
    await page.click('[data-testid="create-event-btn"]')
    await page.fill('[data-testid="reservation-title"]', 'Test Reservation')
    await page.click('[data-testid="save-reservation-btn"]')
    await expect(page.locator('[data-testid="reservation-item"]')).toContainText('Test Reservation')
  })

  test('should edit reservation via drag and drop', async ({ page }) => {
    await page.goto('/admin/calendar')
    const reservation = page.locator('[data-testid="reservation-item"]').first()
    await reservation.dragTo(page.locator('[data-testid="calendar-slot"]').nth(1))
    await expect(reservation).toBeVisible()
  })

  test('should delete reservation with confirmation', async ({ page }) => {
    await page.goto('/admin/calendar')
    await page.click('[data-testid="reservation-item"] >> [data-testid="delete-btn"]')
    await page.click('[data-testid="confirm-delete-btn"]')
    await expect(page.locator('[data-testid="reservation-item"]')).toHaveCount(0)
  })
})

test.describe('Flux Public - Réservation Client', () => {
  test('should complete full booking flow', async ({ page }) => {
    await page.goto('/')
    await page.click('[data-testid="book-now-btn"]')
    await page.fill('[data-testid="guest-name"]', 'John Doe')
    await page.fill('[data-testid="guest-email"]', 'john@example.com')
    await page.click('[data-testid="confirm-booking-btn"]')
    await expect(page.locator('[data-testid="booking-confirmation"]')).toBeVisible()
  })

  test('should save quote for later', async ({ page }) => {
    await page.goto('/')
    await page.click('[data-testid="book-now-btn"]')
    await page.click('[data-testid="save-quote-btn"]')
    await expect(page.locator('[data-testid="quote-saved-msg"]')).toBeVisible()
  })

  test('should request personalized advice', async ({ page }) => {
    await page.goto('/')
    await page.click('[data-testid="book-now-btn"]')
    await page.click('[data-testid="request-advice-btn"]')
    await expect(page.locator('[data-testid="advice-form"]')).toBeVisible()
  })
})

test.describe('Tests d\'Accessibilité', () => {
  test('should be keyboard navigable', async ({ page }) => {
    await loginAsAdmin(page)
    await page.goto('/admin/calendar')
    await page.keyboard.press('Tab')
    await expect(page.locator('[data-testid="calendar-focus"]')).toBeVisible()
  })

  test('should have proper ARIA attributes', async ({ page }) => {
    await page.goto('/')
    await page.click('[data-testid="book-now-btn"]')
    const modal = page.locator('[role="dialog"]')
    await expect(modal).toHaveAttribute('aria-modal', 'true')
  })

  test('should support screen readers', async ({ page }) => {
    await loginAsAdmin(page)
    await page.goto('/admin/dashboard')
    await expect(page.locator('main')).toBeVisible()
  })
})

test.describe('Tests de Performance', () => {
  test('should load calendar quickly', async ({ page }) => {
    const startTime = Date.now()
    await loginAsAdmin(page)
    await page.goto('/admin/calendar')
    await expect(page.locator('[data-testid="fullcalendar"]')).toBeVisible()
    const loadTime = Date.now() - startTime
    console.log(`⏱️ Calendar load time: ${loadTime}ms`)
  })

  test('should handle large datasets efficiently', async ({ page }) => {
    const startTime = Date.now()
    await loginAsAdmin(page)
    await page.goto('/admin/calendar')
    await expect(page.locator('.fc-event')).toHaveCount(100, { timeout: 5000 })
    const renderTime = Date.now() - startTime
    console.log(`⏱️ Calendar render time: ${renderTime}ms`)
  })
})
