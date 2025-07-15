<footer class="site-footer">
  <div class="footer-content">
    <div class="footer-section">
      <h3>FileShare</h3>
      <p>Plateforme de partage et gestion de Données analytiques sécurisée pour professionnels.</p>
    </div>
    <div class="footer-links">
      <a href="https://gael-berru.netlify.app" target="_blank" rel="noopener noreferrer" class="personal-link">
        <i class="fa-solid fa-envelope"></i> berru-g
      </a>
    </div>
  </div>

</footer>

<style>
.site-footer {
  background: #F1F1F1;
  color: grey;
  padding: 30px 0 0;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  margin-top: 40px;
  margin-bottom: 0px;
}

.footer-content {
  display: flex;
  justify-content: space-between;
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
  flex-wrap: wrap;
}

.footer-section {
  flex: 1;
  min-width: 250px;
  margin-bottom: 20px;
}

.footer-section h3 {
  color: grey;
  margin-bottom: 15px;
  font-size: 1.2rem;
}

.footer-section p {
  line-height: 1.6;
  font-size: 0.9rem;
  opacity: 0.8;
}

.footer-links {
  display: flex;
  align-items: center;
}

.personal-link {
  color: grey;
  text-decoration: none;
  display: flex;
  align-items: center;
  padding: 8px 15px;
  border-radius: 4px;
  transition: all 0.3s ease;
  background-color: rgba(255, 255, 255, 0.1);
}

.personal-link:hover {
  background-color: rgba(255, 255, 255, 0.5);
  transform: translateY(-2px);
}

.personal-link i {
  margin-right: 8px;
}


@media (max-width: 768px) {
  .footer-content {
    flex-direction: column;
  }
  
  .footer-links {
    justify-content: center;
    margin-top: 15px;
  }
}
</style>