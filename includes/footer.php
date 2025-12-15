<?php
/**
 * Footer Template
 * 
 * Displays footer content and closes HTML tags
 */
?>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>About Artisan Platform</h3>
                    <p>Connecting artisans and professionals with employers across Nigeria for transparent and efficient digital recruitment.</p>
                </div>

                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="/index.php">Home</a></li>
                        <li><a href="/auth/login.php">Login</a></li>
                        <li><a href="/auth/register.php">Register</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h3>Contact</h3>
                    <p>Email: info@artisanplatform.ng</p>
                    <p>Phone: +234 (0) 123 456 7890</p>
                </div>

                <div class="footer-section">
                    <h3>Follow Us</h3>
                    <ul>
                        <li><a href="#">Facebook</a></li>
                        <li><a href="#">Twitter</a></li>
                        <li><a href="#">LinkedIn</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2024 Artisan Platform. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle">Confirm Action</h3>
            <p id="modalMessage">Are you sure you want to proceed?</p>
            <div class="modal-actions">
                <button id="modalConfirm" class="btn btn-primary">Confirm</button>
                <button id="modalCancel" class="btn btn-secondary">Cancel</button>
            </div>
        </div>
    </div>

    <script src="/assets/js/main.js"></script>
</body>
</html>
