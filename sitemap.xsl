<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:template match="/">
        <html>
            <head>
                <title>Карта сайта</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    h1 { color: #2c3e50; }
                    table { border-collapse: collapse; width: 100%; }
                    th { background: #3498db; color: white; padding: 10px; text-align: left; }
                    td { padding: 8px; border-bottom: 1px solid #ddd; }
                    tr:hover { background: #f5f5f5; }
                    a { color: #2980b9; text-decoration: none; }
                    a:hover { text-decoration: underline; }
                </style>
            </head>
            <body>
                <h1>Карта сайта</h1>
                <table>
                    <thead>
                        <tr>
                            <th>URL</th>
                            <th>Приоритет</th>
                            <th>Частота обновления</th>
                            <th>Последнее изменение</th>
                        </tr>
                    </thead>
                    <tbody>
                        <xsl:for-each select="urlset/url">
                            <tr>
                                <td><a href="{loc}"><xsl:value-of select="loc"/></a></td>
                                <td><xsl:value-of select="priority"/></td>
                                <td><xsl:value-of select="changefreq"/></td>
                                <td><xsl:value-of select="lastmod"/></td>
                            </tr>
                        </xsl:for-each>
                    </tbody>
                </table>
            </body>
        </html>
    </xsl:template>
</xsl:stylesheet>