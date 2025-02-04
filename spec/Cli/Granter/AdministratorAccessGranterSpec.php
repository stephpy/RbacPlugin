<?php

declare(strict_types=1);

namespace spec\Sylius\RbacPlugin\Cli\Granter;

use Doctrine\Persistence\ObjectManager;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\RbacPlugin\Cli\Granter\AdministratorAccessGranterInterface;
use Sylius\RbacPlugin\Entity\AdministrationRoleInterface;
use Tests\Application\RbacPlugin\Entity\AdminUser;

final class AdministratorAccessGranterSpec extends ObjectBehavior
{
    public function let(
        RepositoryInterface $administratorRepository,
        RepositoryInterface $administrationRoleRepository,
        ObjectManager $objectManager
    ): void {
        $this->beConstructedWith($administratorRepository, $administrationRoleRepository, $objectManager);
    }

    public function it_implements_administrator_access_granter_interface(): void
    {
        $this->shouldImplement(AdministratorAccessGranterInterface::class);
    }

    public function it_throws_an_exception_if_administrator_does_not_exist(RepositoryInterface $administratorRepository): void
    {
        $administratorRepository->findOneBy(['email' => 'sylius@example.com'])->willReturn(null);

        $this
            ->shouldThrow(\InvalidArgumentException::class)
            ->during('__invoke', ['sylius@example.com', 'configurator', ['configuration']]);
    }

    public function it_creates_administration_role_if_does_not_exist(
        RepositoryInterface $administratorRepository,
        RepositoryInterface $administrationRoleRepository,
        ObjectManager $objectManager,
        AdminUser $adminUser
    ): void {
        $administratorRepository->findOneBy(['email' => 'sylius@example.com'])->willReturn($adminUser);

        $administrationRoleRepository->findOneBy(['name' => 'Configurator'])->willReturn(null);
        $administrationRoleRepository->add(Argument::that(function (AdministrationRoleInterface $administrationRole): bool {
            return $administrationRole->getName() === 'Configurator';
        }))->shouldBeCalled();

        $adminUser->setAdministrationRole(Argument::that(function (AdministrationRoleInterface $administrationRole): bool {
            return $administrationRole->getName() === 'Configurator';
        }))->shouldBeCalled();

        $objectManager->flush()->shouldBeCalled();

        $this->__invoke('sylius@example.com', 'Configurator', []);
    }

    public function it_assigns_administration_role_to_administrator(
        RepositoryInterface $administratorRepository,
        RepositoryInterface $administrationRoleRepository,
        ObjectManager $objectManager,
        AdminUser $adminUser,
        AdministrationRoleInterface $administrationRole
    ): void {
        $administratorRepository->findOneBy(['email' => 'sylius@example.com'])->willReturn($adminUser);
        $administrationRoleRepository->findOneBy(['name' => 'Configurator'])->willReturn($administrationRole);

        $adminUser->setAdministrationRole($administrationRole)->shouldBeCalled();

        $administrationRoleRepository->add($administrationRole)->shouldBeCalled();
        $objectManager->flush()->shouldBeCalled();

        $this->__invoke('sylius@example.com', 'Configurator', ['catalog', 'configuration', 'customers']);
    }
}
