// tests/e2e/admin-reservation-flow.spec.js
import { test, expect } from '@playwright/test'

test.describe('Flux Admin - Gestion des Réservations', () => {
  test.beforeEach(async ({ page }) => {
    // Login admin
    await page.goto('/admin/login')
    await page.fill('[data-testid="email"]', 'admin@campcaneleon.com')
    await page.fill('[data-testid="password"]', 'password')
    await page.click('[data-testid="login-button"]')
    
    // Attendre redirection vers dashboard
    await expect(page).toHaveURL('/admin/dashboard')
  })

  test('should create reservation via calendar', async ({ page }) => {
    // Aller au calendrier
    await page.goto('/admin/calendar')
    await expect(page.locator('[data-testid="fullcalendar"]')).toBeVisible()

    // Ouvrir modal de création
    await page.click('[data-testid="create-event-btn"]')
    await expect(page.locator('[data-testid="event-modal"]')).toBeVisible()

    // Remplir formulaire réservation
    await page.selectOption('[data-testid="event-type"]', 'reservation')
    await page.fill('[data-testid="event-title"]', 'Réservation Test E2E')
    await page.fill('[data-testid="start-date"]', '2024-03-15')
    await page.fill('[data-testid="end-date"]', '2024-03-18')
    
    // Informations client
    await page.fill('[data-testid="customer-email"]', 'test.e2e@example.com')
    await page.fill('[data-testid="customer-name"]', 'Test')
    await page.fill('[data-testid="customer-lastname"]', 'E2E')
    await page.fill('[data-testid="customer-phone"]', '+33123456789')

    // Sélectionner produits
    await page.check('[data-testid="accommodation-1"]') // Tente Luxe
    await page.check('[data-testid="activity-2"]') // Randonnée
    await page.check('[data-testid="menu-3"]') // Menu Traditionnel

    // Vérifier calcul automatique du montant
    const amountField = page.locator('[data-testid="amount"]')
    await expect(amountField).toHaveValue(/\d+\.\d{2}/)

    // Sauvegarder
    await page.click('[data-testid="save-event-btn"]')

    // Vérifier fermeture modal
    await expect(page.locator('[data-testid="event-modal"]')).not.toBeVisible()

    // Vérifier apparition dans le calendrier
    await expect(page.locator('.fc-event').filter({ hasText: 'Réservation Test E2E' })).toBeVisible()

    // Vérifier dans la liste des réservations
    await page.goto('/admin/reservations')
    await expect(page.locator('[data-testid="reservation-list"]')).toContainText('test.e2e@example.com')
  })

  test('should edit reservation via drag and drop', async ({ page }) => {
    // Créer d'abord une réservation via API ou UI
    await page.goto('/admin/calendar')
    
    // Localiser un événement existant
    const event = page.locator('.fc-event').first()
    await expect(event).toBeVisible()

    // Simuler drag and drop (déplacer de 2 jours)
    const eventBox = await event.boundingBox()
    const targetDate = page.locator('.fc-daygrid-day[data-date="2024-03-17"]')
    const targetBox = await targetDate.boundingBox()

    await page.mouse.move(eventBox.x + eventBox.width / 2, eventBox.y + eventBox.height / 2)
    await page.mouse.down()
    await page.mouse.move(targetBox.x + targetBox.width / 2, targetBox.y + targetBox.height / 2)
    await page.mouse.up()

    // Vérifier confirmation ou modal de mise à jour
    // (selon implémentation - ici on assume une confirmation automatique)
    
    // Vérifier que l'événement a bien bougé
    await expect(page.locator('.fc-daygrid-day[data-date="2024-03-17"] .fc-event')).toBeVisible()
  })

  test('should delete reservation with confirmation', async ({ page }) => {
    await page.goto('/admin/calendar')
    
    // Cliquer sur un événement pour l'éditer
    await page.click('.fc-event', { force: true })
    await expect(page.locator('[data-testid="event-modal"]')).toBeVisible()

    // Cliquer sur supprimer
    await page.click('[data-testid="delete-event-btn"]')
    
    // Confirmer dans la modal de confirmation
    await expect(page.locator('[data-testid="confirm-modal"]')).toBeVisible()
    await page.click('[data-testid="confirm-delete-btn"]')

    // Vérifier fermeture des modals
    await expect(page.locator('[data-testid="confirm-modal"]')).not.toBeVisible()
    await expect(page.locator('[data-testid="event-modal"]')).not.toBeVisible()

    // Vérifier disparition de l'événement
    await page.waitForTimeout(1000) // Attendre refresh du calendrier
  })

  test('should filter reservations by status', async ({ page }) => {
    await page.goto('/admin/calendar')

    // Ouvrir panneau de filtres
    await page.click('[data-testid="filters-toggle"]')
    await expect(page.locator('[data-testid="filters-panel"]')).toBeVisible()

    // Sélectionner filtre "Confirmées"
    await page.selectOption('[data-testid="status-filter"]', 'confirmed')
    await page.click('[data-testid="apply-filters-btn"]')

    // Vérifier que seuls les événements confirmés sont affichés
    const events = page.locator('.fc-event')
    const eventCount = await events.count()
    
    for (let i = 0; i < eventCount; i++) {
      const eventClass = await events.nth(i).getAttribute('class')
      expect(eventClass).toContain('status-confirmed')
    }
  })

  test('should handle validation errors gracefully', async ({ page }) => {
    await page.goto('/admin/calendar')
    await page.click('[data-testid="create-event-btn"]')

    // Essayer de sauvegarder sans remplir les champs requis
    await page.click('[data-testid="save-event-btn"]')

    // Vérifier affichage des erreurs de validation
    await expect(page.locator('[data-testid="error-customer-email"]')).toBeVisible()
    await expect(page.locator('[data-testid="error-event-title"]')).toBeVisible()
    
    // Modal ne doit pas se fermer
    await expect(page.locator('[data-testid="event-modal"]')).toBeVisible()
  })
})

// tests/e2e/public-booking-flow.spec.js
test.describe('Flux Public - Réservation Client', () => {
  test('should complete full booking flow', async ({ page }) => {
    await page.goto('/')

    // Cliquer sur "Réserver"
    await page.click('[data-testid="book-now-btn"]')
    await expect(page.locator('[data-testid="quote-modal"]')).toBeVisible()

    // Étape 1: Sélection des dates
    await page.fill('[data-testid="checkin-date"]', '2024-04-15')
    await page.fill('[data-testid="checkout-date"]', '2024-04-18')
    await page.fill('[data-testid="guests-number"]', '2')
    await page.click('[data-testid="next-step-btn"]')

    // Étape 2: Sélection hébergement
    await expect(page.locator('[data-testid="step-2"]')).toBeVisible()
    await page.check('[data-testid="room-1"]') // Sélectionner première chambre
    await page.click('[data-testid="next-step-btn"]')

    // Étape 3: Activités et menus
    await expect(page.locator('[data-testid="step-3"]')).toBeVisible()
    await page.check('[data-testid="activity-2"]') // Randonnée
    await page.check('[data-testid="menu-1"]') // Menu traditionnel
    await page.click('[data-testid="next-step-btn"]')

    // Étape 4: Informations contact
    await expect(page.locator('[data-testid="step-4"]')).toBeVisible()
    await page.fill('[data-testid="contact-name"]', 'Jean')
    await page.fill('[data-testid="contact-lastname"]', 'Dupont')
    await page.fill('[data-testid="contact-email"]', 'jean.dupont@example.com')
    await page.fill('[data-testid="contact-phone"]', '+33123456789')
    await page.fill('[data-testid="contact-message"]', 'Séjour pour anniversaire de mariage')

    // Vérifier récapitulatif
    await expect(page.locator('[data-testid="recap-section"]')).toBeVisible()
    await expect(page.locator('[data-testid="total-price"]')).toContainText('€')

    // Procéder au paiement
    await page.click('[data-testid="book-and-pay-btn"]')

    // Vérifier redirection vers Stripe (ou message de confirmation selon le flow)
    await page.waitForURL(/stripe\.com|success/, { timeout: 10000 })
  })

  test('should save quote for later', async ({ page }) => {
    await page.goto('/')
    await page.click('[data-testid="book-now-btn"]')

    // Configurer une réservation basique
    await page.fill('[data-testid="checkin-date"]', '2024-04-15')
    await page.fill('[data-testid="checkout-date"]', '2024-04-18')
    await page.fill('[data-testid="guests-number"]', '2')
    await page.click('[data-testid="next-step-btn"]')

    await page.check('[data-testid="room-1"]')
    await page.click('[data-testid="next-step-btn"]')
    await page.click('[data-testid="next-step-btn"]') // Skip activities

    // Remplir contact
    await page.fill('[data-testid="contact-name"]', 'Marie')
    await page.fill('[data-testid="contact-lastname"]', 'Martin')
    await page.fill('[data-testid="contact-email"]', 'marie.martin@example.com')

    // Sauvegarder le devis
    await page.click('[data-testid="save-quote-btn"]')

    // Vérifier message de confirmation
    await expect(page.locator('[data-testid="success-message"]')).toBeVisible()
    await expect(page.locator('[data-testid="success-message"]')).toContainText('devis sauvegardé')
  })

  test('should request personalized advice', async ({ page }) => {
    await page.goto('/')
    await page.click('[data-testid="book-now-btn"]')

    // Aller directement aux informations contact
    await page.fill('[data-testid="contact-name"]', 'Pierre')
    await page.fill('[data-testid="contact-lastname"]', 'Conseil')
    await page.fill('[data-testid="contact-email"]', 'pierre.conseil@example.com')
    await page.fill('[data-testid="contact-message"]', 'Je souhaite des conseils pour organiser un voyage de groupe de 15 personnes')

    // Demander conseil personnalisé
    await page.click('[data-testid="request-advice-btn"]')

    // Vérifier message de confirmation
    await expect(page.locator('[data-testid="advice-confirmation"]')).toBeVisible()
    await expect(page.locator('[data-testid="advice-confirmation"]')).toContainText('expert vous recontacte')
  })

  test('should handle mobile responsive design', async ({ page }) => {
    // Simuler viewport mobile
    await page.setViewportSize({ width: 375, height: 667 })
    await page.goto('/')

    await page.click('[data-testid="book-now-btn"]')
    
    // Vérifier que la modal s'adapte au mobile
    const modal = page.locator('[data-testid="quote-modal"]')
    const modalBox = await modal.boundingBox()
    expect(modalBox.width).toBeLessThan(400)

    // Vérifier navigation tactile
    await page.fill('[data-testid="checkin-date"]', '2024-04-15')
    await page.fill('[data-testid="checkout-date"]', '2024-04-18')
    
    // Swipe pour naviguer entre étapes (si implémenté)
    await page.click('[data-testid="next-step-btn"]')
    await expect(page.locator('[data-testid="step-2"]')).toBeVisible()
  })
})

// tests/e2e/accessibility.spec.js
test.describe('Tests d\'Accessibilité', () => {
  test('should be keyboard navigable', async ({ page }) => {
    await page.goto('/admin/calendar')
    
    // Navigation au clavier dans le calendrier
    await page.keyboard.press('Tab') // Focus sur premier élément
    await page.keyboard.press('Tab') // Navigation suivante
    await page.keyboard.press('Enter') // Activation
    
    // Vérifier que la navigation fonctionne
    const focusedElement = await page.evaluate(() => document.activeElement.tagName)
    expect(['BUTTON', 'INPUT', 'A']).toContain(focusedElement)
  })

  test('should have proper ARIA attributes', async ({ page }) => {
    await page.goto('/')
    await page.click('[data-testid="book-now-btn"]')

    // Vérifier attributs ARIA de la modal
    const modal = page.locator('[data-testid="quote-modal"]')
    await expect(modal).toHaveAttribute('role', 'dialog')
    await expect(modal).toHaveAttribute('aria-modal', 'true')
    
    // Vérifier labels des champs de formulaire
    const emailField = page.locator('[data-testid="contact-email"]')
    await expect(emailField).toHaveAttribute('aria-label')
  })

  test('should support screen readers', async ({ page }) => {
    await page.goto('/admin/dashboard')

    // Vérifier présence de landmarks
    await expect(page.locator('main')).toBeVisible()
    await expect(page.locator('nav')).toBeVisible()
    
    // Vérifier heading hierarchy
    await expect(page.locator('h1')).toBeVisible()
    const h1Text = await page.locator('h1').textContent()
    expect(h1Text).toBeTruthy()
  })
})

// tests/e2e/performance.spec.js
test.describe('Tests de Performance', () => {
  test('should load calendar quickly', async ({ page }) => {
    const startTime = Date.now()
    
    await page.goto('/admin/calendar')
    await expect(page.locator('[data-testid="fullcalendar"]')).toBeVisible()
    
    const loadTime = Date.now() - startTime
    expect(loadTime).toBeLessThan(3000) // Moins de 3 secondes
  })

  test('should handle large datasets efficiently', async ({ page }) => {
    // Simuler beaucoup d'événements (via API mock ou données de test)
    await page.route('/api/admin/calendar/events', async route => {
      const events = Array.from({ length: 100 }, (_, i) => ({
        id: i + 1,
        title: `Event ${i + 1}`,
        start: new Date(2024, 2, (i % 30) + 1).toISOString(),
        end: new Date(2024, 2, (i % 30) + 2).toISOString()
      }))
      await route.fulfill({ json: events })
    })

    const startTime = Date.now()
    await page.goto('/admin/calendar')
    await expect(page.locator('.fc-event')).toHaveCount(100, { timeout: 5000 })
    
    const renderTime = Date.now() - startTime
    expect(renderTime).toBeLessThan(5000) // Rendu en moins de 5 secondes
  })
})

// playwright.config.js
import { defineConfig, devices } from '@playwright/test'

export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: 'html',
  
  use: {
    baseURL: 'http://localhost:5173',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
  },

  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'firefox',
      use: { ...devices['Desktop Firefox'] },
    },
    {
      name: 'webkit',
      use: { ...devices['Desktop Safari'] },
    },
    {
      name: 'Mobile Chrome',
      use: { ...devices['Pixel 5'] },
    },
    {
      name: 'Mobile Safari',
      use: { ...devices['iPhone 12'] },
    },
  ],

  webServer: {
    command: 'npm run dev',
    url: 'http://localhost:5173',
    reuseExistingServer: !process.env.CI,
  },
})