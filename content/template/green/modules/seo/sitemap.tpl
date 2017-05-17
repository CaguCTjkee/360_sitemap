<?xml version="1.0" encoding="UTF-8"?>

{{if $smarty.get.type == 'index'}}

<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    {{foreach from=$data.list item=list}}
        <sitemap>
            <loc>{{$set.host}}{{$list.loc}}</loc>
            <lastmod>{{$list.lastmod}}</lastmod>
        </sitemap>
    {{/foreach}}

    <sitemap>
        <loc>{{$set.host}}/sitemap.xml?type=other</loc>
        <lastmod>{{$smarty.now|date_format:"%Y-%m-%d"}}</lastmod>
    </sitemap>

</sitemapindex>

{{else}}

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">

    {{foreach from=$data.list item=list}}
        <url>
            <loc>{{$set.host}}{{$list.loc}}</loc>
            <lastmod>{{$list.lastmod}}</lastmod>
            <priority>{{if $list.priority}}{{$list.priority}}{{else}}0.8{{/if}}</priority>
        </url>
    {{/foreach}}

</urlset>

{{/if}}