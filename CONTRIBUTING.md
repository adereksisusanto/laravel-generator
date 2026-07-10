# Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/my-feature`)
3. Install dependencies:

   ```bash
   composer install
   ```

4. Make your changes, ensuring code style and static analysis pass:

   ```bash
   composer lint && composer analyse
   ```

5. Write or update tests, then run them:

   ```bash
   composer test
   ```

6. Ensure everything passes with the full check:

   ```bash
   composer check
   ```

7. Commit your changes:

   ```bash
   git add .
   git commit -m "feat: add my feature"
   ```

8. Push to your fork and open a Pull Request
