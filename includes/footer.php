    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3><i class="fas fa-gem"></i> Core Stone Indonesia</h3>
                    <p>Toko online batu akik terpercaya di Indonesia. Menyediakan berbagai jenis batu akik alami berkualitas tinggi dengan harga terbaik.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <div class="footer-col">
                    <h3>Kategori</h3>
                    <ul class="footer-links">
                        <?php
                        try {
                            $stmt = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY sort_order LIMIT 5");
                            while ($cat = $stmt->fetch(PDO::FETCH_ASSOC)):
                        ?>
                            <li><a href="<?php echo SITE_URL; ?>/category.php?slug=<?php echo $cat['slug']; ?>"><?php echo $cat['name']; ?></a></li>
                        <?php 
                            endwhile;
                        } catch (PDOException $e) {}
                        ?>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h3>Layanan Pelanggan</h3>
                    <ul class="footer-links">
                        <li><a href="<?php echo SITE_URL; ?>/about.php">Tentang Kami</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/contact.php">Hubungi Kami</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/shipping.php">Informasi Pengiriman</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/returns.php">Kebijakan Pengembalian</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/faq.php">FAQ</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h3>Kontak Kami</h3>
                    <ul class="footer-links">
                        <li><i class="fas fa-map-marker-alt"></i> Indonesia</li>
                        <li><i class="fas fa-phone"></i> 0812-1493-2916</li>
                        <li><i class="fas fa-envelope"></i> admin@corestone.id</li>
                        <li><i class="fas fa-clock"></i> Senin - Sabtu: 08:00 - 20:00</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Core Stone Indonesia. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- WhatsApp Float Button -->
    <a href="https://wa.me/6281214932916?text=Halo%20Core%20Stone%20Indonesia,%20saya%20tertarik%20dengan%20produk%20Anda" 
       class="whatsapp-float" 
       target="_blank" 
       rel="noopener noreferrer"
       aria-label="Chat WhatsApp">
        <i class="fab fa-whatsapp"></i>
    </a>

    <!-- Main JavaScript -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
