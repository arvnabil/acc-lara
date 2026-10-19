import { Head } from '@inertiajs/react';

const siteName = 'Accommerce.id';

function absoluteUrl(path) {
  if (!path) return null;
  if (/^https?:\/\//i.test(path)) return path;
  return `${window.location.origin}${path.startsWith('/') ? path : `/${path}`}`;
}

export default function SeoHead({
  seo,
  title,
  description,
  canonical,
  image,
  type = 'website',
  noindex = false,
  schema,
}) {
  const seoTags = seo?.tags || {};
  const resolvedTitle = seoTags.title || title;
  const resolvedDescription = seoTags.description || description;
  const resolvedCanonical = seoTags.canonical_url || canonical || window.location.pathname;
  const resolvedImage = seoTags.og_image || image;
  const resolvedNoindex = noindex || Boolean(seoTags.noindex);
  const resolvedSchema = schema || seo?.schema;
  const fullTitle = resolvedTitle ? `${resolvedTitle} | ${siteName}` : siteName;
  const canonicalUrl = absoluteUrl(resolvedCanonical);
  const imageUrl = absoluteUrl(resolvedImage);

  return (
    <Head title={fullTitle}>
      {resolvedDescription && <meta name="description" content={resolvedDescription} />}
      <meta name="robots" content={resolvedNoindex ? 'noindex,follow' : 'index,follow'} />
      {canonicalUrl && <link rel="canonical" href={canonicalUrl} />}
      <meta property="og:type" content={type} />
      <meta property="og:site_name" content={siteName} />
      <meta property="og:title" content={fullTitle} />
      {resolvedDescription && <meta property="og:description" content={resolvedDescription} />}
      {canonicalUrl && <meta property="og:url" content={canonicalUrl} />}
      {imageUrl && <meta property="og:image" content={imageUrl} />}
      <meta name="twitter:card" content={imageUrl ? 'summary_large_image' : 'summary'} />
      <meta name="twitter:title" content={fullTitle} />
      {resolvedDescription && <meta name="twitter:description" content={resolvedDescription} />}
      {imageUrl && <meta name="twitter:image" content={imageUrl} />}
      {resolvedSchema && (
        <script head-key="jsonld" type="application/ld+json">
          {JSON.stringify(resolvedSchema)}
        </script>
      )}
    </Head>
  );
}
