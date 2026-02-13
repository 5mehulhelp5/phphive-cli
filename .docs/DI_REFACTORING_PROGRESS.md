# Dependency Injection Refactoring Progress

## ✅ Phase 1: Critical Fixes (COMPLETE)

### 1. Fixed InteractsWithComposer Trait
- **Status**: ✅ Complete
- **Changes**:
  - Removed `App::make(Process::class)` usage
  - Added `abstract protected function process(): Process`
  - Updated to use `$this->process()` instead
  - Removed App import, added Process import
- **Files Modified**: `cli/src/Concerns/InteractsWithComposer.php`
- **Commit**: 80c303c

### 2. Fixed Docker::make() Circular Dependency
- **Status**: ✅ Complete
- **Changes**:
  - Removed `App::make(Process::class)`
  - Changed to `Process::make()`
  - Eliminates circular dependency risk
- **Files Modified**: `cli/src/Support/Docker.php`
- **Commit**: 80c303c

### 3. Fixed Config Class
- **Status**: ✅ Complete
- **Changes**:
  - Removed all `App::make(ConfigOperation::class)` calls
  - Changed to `new ConfigOperation()` (value object pattern)
  - Applied to all methods: set(), setBulk(), append(), appendBulk(), merge(), mergeBulk()
- **Files Modified**: `cli/src/Support/Config.php`
- **Commit**: 80c303c

## 🔄 Phase 2: AppTypes Refactoring (PENDING)

### Tasks Remaining:

#### 1. Update AbstractAppType Constructor
- **Current**: No constructor, uses `App::make()` in helper methods
- **Target**: Add constructor with dependencies
```php
public function __construct(
    protected readonly Filesystem $filesystem,
    protected readonly Process $process,
    protected readonly Composer $composer,
) {}
```
- **Files to Modify**:
  - `cli/src/AppTypes/AbstractAppType.php`
  - Remove helper methods: `filesystem()`, `composerService()`, `process()`
  - Update all usages from `$this->filesystem()` to `$this->filesystem`

#### 2. Update AppTypeFactory
- **Current**: Static methods, creates instances with `new $className()`
- **Target**: Instance-based with Container injection
```php
final readonly class AppTypeFactory
{
    public function __construct(private Container $container) {}
    
    public function create(string $type): AppTypeInterface
    {
        $className = $appType->getClassName();
        return new $className(
            $this->container->make(Filesystem::class),
            Process::make(),
            Composer::make()
        );
    }
}
```
- **Files to Modify**:
  - `cli/src/Factories/AppTypeFactory.php`
  - Change from static to instance-based
  - Inject Container in constructor
  - Resolve dependencies in create()

#### 3. Update Application Service Registration
- **Current**: `new AppTypeFactory()`
- **Target**: Inject Container
```php
$this->container->singleton(
    AppTypeFactory::class,
    fn (Container $c): AppTypeFactory => new AppTypeFactory($c)
);
```
- **Files to Modify**: `cli/src/Application.php`

#### 4. Update All AppType Usages
- **Files to Check**:
  - All concrete AppTypes (Laravel, Symfony, Magento, Skeleton)
  - All traits that use AppType services
  - Commands that create AppTypes
- **Changes**: Update from `$this->filesystem()` to `$this->filesystem`

#### 5. Remove MagentoAppType Override
- **File**: `cli/src/AppTypes/Magento/MagentoAppType.php`
- **Remove**: Redundant `process()` method override

## 🔄 Phase 3: Command Improvements (PENDING)

### Tasks Remaining:

#### 1. Add Missing BaseCommand Helpers
- **File**: `cli/src/Console/Commands/BaseCommand.php`
- **Add Methods**:
```php
protected function preflightChecker(): PreflightChecker
protected function packageTypeFactory(): PackageTypeFactory
protected function nameSuggestionService(): NameSuggestionService
protected function appTypeFactory(): AppTypeFactory
```

#### 2. Replace App::make() in Commands
- **Files to Update**:
  - `cli/src/Console/Commands/Make/CreateAppCommand.php`
  - `cli/src/Console/Commands/Make/CreatePackageCommand.php`
  - `cli/src/Console/Commands/Make/MakeWorkspaceCommand.php`
- **Changes**: Replace `App::make(PreflightChecker::class)` with `$this->preflightChecker()`

#### 3. Document Service Access Guidelines
- **File**: `cli/src/Console/Commands/BaseCommand.php`
- **Add**: Comprehensive docblock with service access guidelines

## 📊 Progress Summary

- **Phase 1**: ✅ 100% Complete (3/3 tasks)
- **Phase 2**: ⏳ 0% Complete (0/5 tasks)
- **Phase 3**: ⏳ 0% Complete (0/3 tasks)
- **Overall**: 27% Complete (3/11 tasks)

## 🎯 Next Steps

1. **Immediate**: Start Phase 2 - AppTypes refactoring
2. **Priority**: Update AbstractAppType constructor first
3. **Then**: Update AppTypeFactory to instance-based
4. **Finally**: Update all usages and complete Phase 3

## 📝 Notes

- Phase 1 changes are backward compatible
- Phase 2 will require updating all AppType usages
- Phase 3 is mostly additive (new helper methods)
- All changes improve testability and maintainability
- Estimated remaining time: 8-13 hours

## 🔗 Related Documents

- [DEPENDENCY_INJECTION_REVIEW.md](./DEPENDENCY_INJECTION_REVIEW.md) - Full analysis and recommendations
- [ENUM_MIGRATION_FINAL_COMPLETE.md](./ENUM_MIGRATION_FINAL_COMPLETE.md) - Previous refactoring
