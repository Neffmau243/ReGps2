<template>
  <footer class="footer">
    <div class="footer-container">
      <!-- Sección principal del footer -->
      <div class="footer-content">
        <!-- Logo y descripción -->
        <div class="footer-section">
          <div class="footer-logo">
            <i class="bi bi-broadcast text-xl"></i>
            <span class="logo-text">Re<span class="text-primary">GPS</span></span>
          </div>
          <p class="footer-description">
            Sistema de rastreo GPS en tiempo real con geofencing inteligente y alertas automáticas.
          </p>
          <div class="footer-social">
            <a href="#" class="social-link" aria-label="Facebook">
              <i class="bi bi-facebook"></i>
            </a>
            <a href="#" class="social-link" aria-label="Twitter">
              <i class="bi bi-twitter-x"></i>
            </a>
            <a href="#" class="social-link" aria-label="LinkedIn">
              <i class="bi bi-linkedin"></i>
            </a>
            <a href="#" class="social-link" aria-label="GitHub">
              <i class="bi bi-github"></i>
            </a>
          </div>
        </div>

        <!-- Enlaces rápidos -->
        <div class="footer-section">
          <h3 class="footer-title">Enlaces Rápidos</h3>
          <ul class="footer-links">
            <li>
              <router-link to="/" class="footer-link">
                <i class="bi bi-speedometer2"></i>
                Dashboard
              </router-link>
            </li>
            <li>
              <router-link to="/historial" class="footer-link">
                <i class="bi bi-clock-history"></i>
                Historial
              </router-link>
            </li>
            <li>
              <router-link to="/zonas" class="footer-link">
                <i class="bi bi-geo-alt-fill"></i>
                Zonas
              </router-link>
            </li>
            <li>
              <router-link to="/alertas" class="footer-link">
                <i class="bi bi-bell-fill"></i>
                Alertas
              </router-link>
            </li>
          </ul>
        </div>

        <!-- Recursos -->
        <div class="footer-section">
          <h3 class="footer-title">Recursos</h3>
          <ul class="footer-links">
            <li>
              <a href="#" class="footer-link">
                <i class="bi bi-book-fill"></i>
                Documentación
              </a>
            </li>
            <li>
              <a href="#" class="footer-link">
                <i class="bi bi-question-circle-fill"></i>
                Ayuda y Soporte
              </a>
            </li>
            <li>
              <a href="#" class="footer-link">
                <i class="bi bi-code-slash"></i>
                API
              </a>
            </li>
            <li>
              <a href="#" class="footer-link">
                <i class="bi bi-shield-check"></i>
                Seguridad
              </a>
            </li>
          </ul>
        </div>

        <!-- Contacto -->
        <div class="footer-section">
          <h3 class="footer-title">Contacto</h3>
          <ul class="footer-contact">
            <li class="contact-item">
              <i class="bi bi-envelope-fill"></i>
              <a href="mailto:info@regps.com">info@regps.com</a>
            </li>
            <li class="contact-item">
              <i class="bi bi-telephone-fill"></i>
              <a href="tel:+1234567890">+1 (234) 567-890</a>
            </li>
            <li class="contact-item">
              <i class="bi bi-geo-alt-fill"></i>
              <span>Ciudad, País</span>
            </li>
          </ul>
        </div>
      </div>

      <!-- Línea divisoria -->
      <div class="footer-divider"></div>

      <!-- Copyright -->
      <div class="footer-bottom">
        <div class="footer-copyright">
          <p>© {{ currentYear }} <span class="text-primary">ReGPS</span>. Todos los derechos reservados.</p>
        </div>
        <div class="footer-legal">
          <a href="#" class="legal-link">Términos de Servicio</a>
          <span class="separator">|</span>
          <a href="#" class="legal-link">Política de Privacidad</a>
          <span class="separator">|</span>
          <a href="#" class="legal-link">Cookies</a>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const currentYear = computed(() => new Date().getFullYear())
</script>

<style scoped>
.footer {
  background: linear-gradient(180deg, var(--color-dark-100) 0%, var(--color-dark) 100%);
  border-top: 1px solid var(--color-dark-300);
  margin-top: auto;
  padding: 3rem 0 1.5rem;
}

.footer-container {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 1rem;
}

.footer-content {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 2rem;
  margin-bottom: 2rem;
}

/* Logo section */
.footer-section {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.footer-logo {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.5rem;
}

.logo-icon {
  width: 2.5rem;
  height: 2.5rem;
  background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
  border-radius: 0.625rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  color: white;
}

.logo-text {
  font-size: 1.5rem;
  font-weight: 700;
  color: white;
}

.footer-description {
  color: var(--color-gray-400);
  font-size: 0.875rem;
  line-height: 1.6;
}

.footer-social {
  display: flex;
  gap: 0.75rem;
}

.social-link {
  width: 2.25rem;
  height: 2.25rem;
  background-color: var(--color-dark-200);
  border: 1px solid var(--color-dark-300);
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-gray-400);
  font-size: 1rem;
  transition: all var(--transition-fast);
  text-decoration: none;
}

.social-link:hover {
  background-color: var(--color-primary);
  border-color: var(--color-primary);
  color: white;
  transform: translateY(-3px);
}

/* Títulos de sección */
.footer-title {
  color: white;
  font-size: 1rem;
  font-weight: 700;
  margin-bottom: 1rem;
  position: relative;
  padding-bottom: 0.5rem;
}

.footer-title::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  width: 2rem;
  height: 2px;
  background: linear-gradient(90deg, var(--color-primary) 0%, transparent 100%);
}

/* Enlaces */
.footer-links {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0.625rem;
}

.footer-link {
  color: var(--color-gray-400);
  text-decoration: none;
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all var(--transition-fast);
  padding: 0.25rem 0;
}

.footer-link:hover {
  color: var(--color-primary);
  transform: translateX(5px);
}

.footer-link i {
  font-size: 0.875rem;
}

/* Contacto */
.footer-contact {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0.875rem;
}

.contact-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: var(--color-gray-400);
  font-size: 0.875rem;
}

.contact-item i {
  color: var(--color-primary);
  font-size: 1rem;
  width: 1.25rem;
  text-align: center;
}

.contact-item a {
  color: var(--color-gray-400);
  text-decoration: none;
  transition: color var(--transition-fast);
}

.contact-item a:hover {
  color: var(--color-primary);
}

/* Divisor */
.footer-divider {
  height: 1px;
  background: linear-gradient(90deg, transparent 0%, var(--color-dark-300) 50%, transparent 100%);
  margin: 2rem 0 1.5rem;
}

/* Footer bottom */
.footer-bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.footer-copyright {
  color: var(--color-gray-500);
  font-size: 0.875rem;
}

.footer-legal {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.legal-link {
  color: var(--color-gray-500);
  text-decoration: none;
  font-size: 0.875rem;
  transition: color var(--transition-fast);
}

.legal-link:hover {
  color: var(--color-primary);
}

.separator {
  color: var(--color-gray-600);
  user-select: none;
}

/* ===============================================
   RESPONSIVE DESIGN
   =============================================== */

/* Mobile (< 640px) */
@media (max-width: 640px) {
  .footer {
    padding: 2rem 0 1rem;
  }

  .footer-content {
    grid-template-columns: 1fr;
    gap: 2rem;
  }

  .footer-section:first-child {
    text-align: center;
  }

  .footer-logo {
    justify-content: center;
  }

  .footer-social {
    justify-content: center;
  }

  .footer-title {
    text-align: center;
  }

  .footer-title::after {
    left: 50%;
    transform: translateX(-50%);
  }

  .footer-links,
  .footer-contact {
    align-items: center;
  }

  .footer-bottom {
    flex-direction: column;
    text-align: center;
  }

  .footer-legal {
    justify-content: center;
  }
}

/* Tablet (641px - 1024px) */
@media (min-width: 641px) and (max-width: 1024px) {
  .footer-content {
    grid-template-columns: repeat(2, 1fr);
    gap: 2.5rem;
  }
}

/* Desktop large (> 1024px) */
@media (min-width: 1025px) {
  .footer-content {
    grid-template-columns: 2fr 1fr 1fr 1fr;
  }
}
</style>
