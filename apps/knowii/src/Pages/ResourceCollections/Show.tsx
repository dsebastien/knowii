import AppLayout from '@/Layouts/AppLayout';
import { Community, CommunityPermissions, CommunityResourceCollection, COMMUNITY_URL, useSocket, DASHBOARD_URL } from '@knowii/common';
import { Link, router } from '@inertiajs/react';
import { MenuItem } from 'primereact/menuitem';
import { breadcrumbHome } from '@/Components/BreadcrumbHome';
import CommunityIcon from '@/Components/Communities/CommunityIcon';
import ResourceCollectionIcon from '@/Components/ResourceCollections/ResourceCollectionIcon';

interface Props {
  community: Community;
  permissions: CommunityPermissions;
  resourceCollection: CommunityResourceCollection;
}

export default function ResourceCollectionPage(props: Props) {
  useSocket({
    channel: {
      type: 'community',
      communityCuid: props.community.cuid,
    },
    event: 'community.deleted',
    callback: (_event, _deletedCommunity) => {
      // The current community has been deleted. Can't stay here anymore
      router.visit(route(DASHBOARD_URL), {
        preserveState: false,
      });
    },
  });

  useSocket({
    channel: {
      type: 'resourceCollection',
      communityCuid: props.community.cuid,
      resourceCollectionCuid: props.resourceCollection.cuid,
    },
    event: 'community.resource_collection.deleted',
    callback: (_event, _deletedResourceCollection) => {
      // The current resource collection has been deleted. Can't stay here anymore
      router.visit(route(COMMUNITY_URL, { communitySlug: props.community.slug }), {
        preserveState: false,
      });
    },
  });

  const breadcrumbItems: MenuItem[] = [
    {
      label: props.community.name,
      template: (item) => (
        <Link href={route(COMMUNITY_URL, { communitySlug: props.community.slug })} preserveState={true}>
          <span className="flex items-center gap-2">
            <CommunityIcon visibility={props.community.visibility} />
            {item.label}
          </span>
        </Link>
      ),
    },
    {
      label: props.resourceCollection.name,
      template: (item) => (
        <span className="flex items-center gap-2">
          <ResourceCollectionIcon />
          {item.label}
        </span>
      ),
    },
  ];

  return (
    <AppLayout
      browserPageTitle={`${props.community.name} - ${props.resourceCollection.name}`}
      breadcrumbItems={breadcrumbItems}
      breadcrumbHome={breadcrumbHome}
    >
      Coming soon...
    </AppLayout>
  );
}
