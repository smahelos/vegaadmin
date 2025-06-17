---
mode: 'agent'
description: 'Deployment and production environment instructions'
---

# Deployment and Production Instructions

## Pre-deployment Checklist
- [ ] All tests pass
- [ ] Code is properly documented
- [ ] Environment variables are set
- [ ] Database migrations are ready
- [ ] Assets are compiled and optimized
- [ ] Security vulnerabilities checked

## Production Environment Setup
- Use production-ready database (MySQL/PostgreSQL)
- Configure proper caching (Redis)
- Set up queue workers for background jobs
- Configure proper logging and monitoring
- Use HTTPS with SSL certificates

## Environment Configuration
- Set `APP_ENV=production`
- Set `APP_DEBUG=false`
- Use strong `APP_KEY`
- Configure database connections
- Set up mail configuration
- Configure cache and session drivers

## Performance Optimization
- Enable OPcache for PHP
- Use Redis for caching and sessions
- Optimize database queries
- Compress and minify assets
- Use CDN for static assets

## Security Measures
- Regular security updates
- Proper file permissions
- Database user with minimal privileges
- Rate limiting on API endpoints
- CSRF protection enabled
- Input validation and sanitization

## Monitoring and Logging
- Set up application monitoring
- Configure error tracking
- Monitor database performance
- Track user activities
- Set up backup procedures

## Docker Production Setup
- Use multi-stage builds
- Optimize Docker images
- Use proper secrets management
- Configure health checks
- Set up container orchestration
