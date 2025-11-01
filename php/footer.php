    <!-- Footer -->
    <footer style="background: #333; color: white; padding: 3rem 0; margin-top: 4rem;">
        <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 1rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
                <div>
                    <h3 style="margin-bottom: 1rem; color: #fff;"><i class="fas fa-hotel"></i> HotelRes</h3>
                    <p style="margin: 0.5rem 0; line-height: 1.6;">Votre destination de luxe pour un séjour inoubliable. Réservez dès maintenant et profitez d'une expérience exceptionnelle.</p>
                </div>
                <div>
                    <h3 style="margin-bottom: 1rem; color: #fff;"><i class="fas fa-phone"></i> Contact</h3>
                    <p style="margin: 0.5rem 0;"><i class="fas fa-envelope"></i> Email: contact@hotel.com</p>
                    <p style="margin: 0.5rem 0;"><i class="fas fa-phone"></i> Téléphone: +237 6XX XXX XXX</p>
                    <p style="margin: 0.5rem 0;"><i class="fas fa-map-marker-alt"></i> Adresse: Yaoundé, Cameroun</p>
                </div>
                <div>
                    <h3 style="margin-bottom: 1rem; color: #fff;"><i class="fas fa-link"></i> Liens rapides</h3>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="margin: 0.5rem 0;"><a href="<?php echo isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], '/php/') !== false ? '../index.html' : 'index.html'; ?>" style="color: white; text-decoration: none; transition: color 0.3s;"><i class="fas fa-home"></i> Accueil</a></li>
                        <li style="margin: 0.5rem 0;"><a href="<?php echo isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], '/php/') !== false ? 'rooms.php' : 'php/rooms.php'; ?>" style="color: white; text-decoration: none; transition: color 0.3s;"><i class="fas fa-bed"></i> Chambres</a></li>
                        <li style="margin: 0.5rem 0;"><a href="<?php echo isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], '/php/') !== false ? 'search_rooms.php' : 'php/search_rooms.php'; ?>" style="color: white; text-decoration: none; transition: color 0.3s;"><i class="fas fa-search"></i> Recherche</a></li>
                        <li style="margin: 0.5rem 0;"><a href="<?php echo isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], '/php/') !== false ? 'contact.php' : 'php/contact.php'; ?>" style="color: white; text-decoration: none; transition: color 0.3s;"><i class="fas fa-envelope"></i> Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h3 style="margin-bottom: 1rem; color: #fff;"><i class="fas fa-share-alt"></i> Suivez-nous</h3>
                    <div style="display: flex; gap: 1rem; font-size: 1.5rem;">
                        <a href="#" style="color: white; transition: color 0.3s;" title="Facebook"><i class="fab fa-facebook"></i></a>
                        <a href="#" style="color: white; transition: color 0.3s;" title="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" style="color: white; transition: color 0.3s;" title="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" style="color: white; transition: color 0.3s;" title="LinkedIn"><i class="fab fa-linkedin"></i></a>
                    </div>
                    <div style="margin-top: 1.5rem;">
                        <p style="margin: 0.5rem 0;"><i class="fas fa-clock"></i> Disponible 24/7</p>
                        <p style="margin: 0.5rem 0;"><i class="fas fa-shield-alt"></i> Paiement sécurisé</p>
                    </div>
                </div>
            </div>
            <div style="text-align: center; padding-top: 2rem; border-top: 1px solid #555;">
                <p style="margin: 0;">&copy; <?php echo date('Y'); ?> HotelRes. Tous droits réservés. | Développé avec <i class="fas fa-heart" style="color: #e74c3c;"></i></p>
            </div>
        </div>
    </footer>

    <style>
        footer a:hover {
            color: #0066cc !important;
        }
    </style>
