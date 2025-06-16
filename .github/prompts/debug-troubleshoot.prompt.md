---
mode: 'agent'
description: 'Debugging and troubleshooting guide for Laravel/Backpack application'
---

# Debugging and Troubleshooting

## Laravel Debugging Tools
- Use `dd()` and `dump()` for variable inspection
- Check Laravel logs in `storage/logs/`
- Use `php artisan tinker` for interactive debugging
- Enable debug mode in `.env` (APP_DEBUG=true)

## Common Issues and Solutions

### Database Issues
- Check database connection in `.env`
- Run migrations: `php artisan migrate`
- Clear database cache: `php artisan db:cache:clear`
- Check database permissions and credentials

### Cache Issues
- Clear application cache: `php artisan cache:clear`
- Clear config cache: `php artisan config:clear`
- Clear route cache: `php artisan route:clear`
- Clear view cache: `php artisan view:clear`
- Full optimization clear: `php artisan optimize:clear`

### Permission Issues (Backpack)
- Check user roles and permissions in database
- Verify permission middleware is applied
- Use `php artisan permission:cache-reset`
- Check permission guard names

### Validation Issues
- Check Form Request validation rules
- Verify translation keys exist
- Test validation with different locales
- Check custom validation messages

### Frontend Issues
- Rebuild assets: `npm run build`
- Check for JavaScript errors in browser console
- Verify Tailwind CSS compilation
- Check for missing CSS/JS files

## Docker-Specific Debugging
- Always use Docker container for artisan commands
- Check container logs: `docker logs vegaadmin-app`
- Verify container is running: `docker ps`
- Access container shell: `docker exec -it vegaadmin-app bash`

## Testing Issues
- Use `RefreshDatabase` trait for database tests
- Check test database configuration
- Run specific test: `php artisan test --filter TestName`
- Use `--stop-on-failure` flag for debugging

## Performance Debugging
- Use Laravel Debugbar for development
- Check N+1 query problems
- Use eager loading for relationships
- Monitor slow query log
