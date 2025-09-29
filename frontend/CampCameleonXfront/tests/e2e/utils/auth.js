// tests/e2e/utils/auth.js
export async function loginAsAdmin(page) {
  await page.goto('/admin/login')
  await page.fill('[data-testid="email"]', 'admin@campcameleon.com') // ⚠️ vérifie bien ton email
  await page.fill('[data-testid="password"]', 'password')            // ⚠️ idem mot de passe
  await page.click('[data-testid="login-button"]')
  // Attendre la redirection vers le dashboard
  await page.waitForURL('**/admin/dashboard')
}
