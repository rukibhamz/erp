<#
    Generates the default social share image, favicons and PWA icons.

    Usage (from the project root):
        powershell -ExecutionPolicy Bypass -File tools\generate_seo_images.ps1
        powershell -ExecutionPolicy Bypass -File tools\generate_seo_images.ps1 -SiteName "Acropolis Park" -Domain "acropolispark.com"

    A custom share image can also be uploaded in System Settings > SEO, which overrides og-image.png.
#>
param(
    [string]$SiteName = 'Acropolis Park',
    [string]$Tagline = 'Book event spaces, picnics, photo & video shoots and workspaces online.',
    [string]$Domain = 'acropolispark.com',
    [string]$Primary = '#0066CC'
)

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Drawing

$root = Split-Path -Parent $PSScriptRoot
$imgDir = Join-Path $root 'assets\img'
New-Item -ItemType Directory -Force -Path $imgDir | Out-Null

$fonts = New-Object System.Drawing.Text.PrivateFontCollection
$fontDirs = @("$env:WINDIR\Fonts", "$env:LOCALAPPDATA\Microsoft\Windows\Fonts")
foreach ($name in 'Poppins-Bold.ttf', 'Poppins-Medium.ttf', 'Poppins-Regular.ttf') {
    foreach ($dir in $fontDirs) {
        $path = Join-Path $dir $name
        if (Test-Path $path) { $fonts.AddFontFile($path); break }
    }
}
$family = if ($fonts.Families.Count -gt 0) { $fonts.Families[0] } else { New-Object System.Drawing.FontFamily 'Segoe UI' }

function New-Color([string]$hex, [int]$alpha = 255) {
    $c = [System.Drawing.ColorTranslator]::FromHtml($hex)
    return [System.Drawing.Color]::FromArgb($alpha, $c.R, $c.G, $c.B)
}

function New-RoundedPath([single]$x, [single]$y, [single]$w, [single]$h, [single]$r) {
    $p = New-Object System.Drawing.Drawing2D.GraphicsPath
    $d = $r * 2
    $p.AddArc($x, $y, $d, $d, 180, 90)
    $p.AddArc($x + $w - $d, $y, $d, $d, 270, 90)
    $p.AddArc($x + $w - $d, $y + $h - $d, $d, $d, 0, 90)
    $p.AddArc($x, $y + $h - $d, $d, $d, 90, 90)
    $p.CloseFigure()
    return $p
}

function New-Canvas([int]$w, [int]$h) {
    $bmp = New-Object System.Drawing.Bitmap $w, $h, ([System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
    $g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    $g.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
    $g.TextRenderingHint = [System.Drawing.Text.TextRenderingHint]::AntiAliasGridFit
    return @($bmp, $g)
}

# Calendar-with-check glyph (matches the bi-calendar-check icon used in the portal header)
function Draw-CalendarGlyph($g, [single]$x, [single]$y, [single]$s, $color) {
    $stroke = [Math]::Max(1.5, $s * 0.065)
    $pen = New-Object System.Drawing.Pen $color, $stroke
    $pen.StartCap = [System.Drawing.Drawing2D.LineCap]::Round
    $pen.EndCap = [System.Drawing.Drawing2D.LineCap]::Round
    $pen.LineJoin = [System.Drawing.Drawing2D.LineJoin]::Round

    $body = New-RoundedPath ($x + $s * 0.17) ($y + $s * 0.24) ($s * 0.66) ($s * 0.60) ($s * 0.09)
    $g.DrawPath($pen, $body)
    $g.DrawLine($pen, ($x + $s * 0.17), ($y + $s * 0.42), ($x + $s * 0.83), ($y + $s * 0.42))
    $g.DrawLine($pen, ($x + $s * 0.36), ($y + $s * 0.15), ($x + $s * 0.36), ($y + $s * 0.30))
    $g.DrawLine($pen, ($x + $s * 0.64), ($y + $s * 0.15), ($x + $s * 0.64), ($y + $s * 0.30))

    $check = [System.Drawing.PointF[]]@(
        (New-Object System.Drawing.PointF ($x + $s * 0.37), ($y + $s * 0.62)),
        (New-Object System.Drawing.PointF ($x + $s * 0.47), ($y + $s * 0.72)),
        (New-Object System.Drawing.PointF ($x + $s * 0.64), ($y + $s * 0.54))
    )
    $g.DrawLines($pen, $check)
    $pen.Dispose(); $body.Dispose()
}

function Save-Icon([int]$size, [string]$file, [bool]$rounded) {
    $canvas = New-Canvas $size $size
    $bmp = $canvas[0]; $g = $canvas[1]
    $fill = New-Object System.Drawing.SolidBrush (New-Color $Primary)
    if ($rounded) {
        $tile = New-RoundedPath 0 0 $size $size ($size * 0.22)
        $g.FillPath($fill, $tile); $tile.Dispose()
    } else {
        $g.FillRectangle($fill, 0, 0, $size, $size)
    }
    # Full-bleed icons keep the glyph inside the maskable safe zone (centre 80%)
    $inset = if ($rounded) { 0 } else { $size * 0.12 }
    Draw-CalendarGlyph $g $inset $inset ($size - 2 * $inset) ([System.Drawing.Color]::White)
    $bmp.Save((Join-Path $imgDir $file), [System.Drawing.Imaging.ImageFormat]::Png)
    $fill.Dispose(); $g.Dispose(); $bmp.Dispose()
}

function Draw-Pill($g, [string]$text, [single]$x, [single]$y, $font) {
    $size = $g.MeasureString($text, $font)
    $w = $size.Width + 40; $h = 52
    $path = New-RoundedPath $x $y $w $h 26
    $bg = New-Object System.Drawing.SolidBrush (New-Color '#FFFFFF' 18)
    $border = New-Object System.Drawing.Pen (New-Color '#FFFFFF' 60), 1.5
    $g.FillPath($bg, $path); $g.DrawPath($border, $path)
    $brush = New-Object System.Drawing.SolidBrush (New-Color '#E2E8F0')
    $g.DrawString($text, $font, $brush, ($x + 20), ($y + ($h - $size.Height) / 2 + 1))
    $path.Dispose(); $bg.Dispose(); $border.Dispose(); $brush.Dispose()
    return $w
}

# --- Social share image (1200x630) ---
$W = 1200; $H = 630
$canvas = New-Canvas $W $H
$bmp = $canvas[0]; $g = $canvas[1]

$bgRect = New-Object System.Drawing.Rectangle 0, 0, $W, $H
$bg = New-Object System.Drawing.Drawing2D.LinearGradientBrush $bgRect, (New-Color '#07090D'), (New-Color '#172033'), 25
$g.FillRectangle($bg, $bgRect)

foreach ($ring in @(@(860, -180, 620, 22), @(980, 230, 420, 16), @(-160, 430, 380, 10))) {
    $glow = New-Object System.Drawing.SolidBrush (New-Color $Primary $ring[3])
    $g.FillEllipse($glow, $ring[0], $ring[1], $ring[2], $ring[2])
    $glow.Dispose()
}

$accent = New-Object System.Drawing.SolidBrush (New-Color $Primary)
$g.FillRectangle($accent, 0, 0, $W, 8)

$tile = New-RoundedPath 80 78 112 112 26
$g.FillPath($accent, $tile)
Draw-CalendarGlyph $g 80 78 112 ([System.Drawing.Color]::White)

$labelFont = New-Object System.Drawing.Font $family, 22, ([System.Drawing.FontStyle]::Regular), ([System.Drawing.GraphicsUnit]::Pixel)
$labelBrush = New-Object System.Drawing.SolidBrush (New-Color '#93C5FD')
$g.DrawString('BOOKING PORTAL', $labelFont, $labelBrush, 214, 118)

$titleFont = New-Object System.Drawing.Font $family, 86, ([System.Drawing.FontStyle]::Bold), ([System.Drawing.GraphicsUnit]::Pixel)
$white = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::White)
$g.DrawString($SiteName, $titleFont, $white, 72, 222)

$tagFont = New-Object System.Drawing.Font $family, 34, ([System.Drawing.FontStyle]::Regular), ([System.Drawing.GraphicsUnit]::Pixel)
$tagBrush = New-Object System.Drawing.SolidBrush (New-Color '#CBD5E1')
$tagRect = New-Object System.Drawing.RectangleF 80, 342, 900, 110
$g.DrawString($Tagline, $tagFont, $tagBrush, $tagRect)

$pillFont = New-Object System.Drawing.Font $family, 22, ([System.Drawing.FontStyle]::Regular), ([System.Drawing.GraphicsUnit]::Pixel)
$px = 80
foreach ($text in 'Live availability', 'Instant confirmation', 'Secure online payment') {
    $px += (Draw-Pill $g $text $px 490 $pillFont) + 14
}

if ($Domain) {
    $domainFont = New-Object System.Drawing.Font $family, 22, ([System.Drawing.FontStyle]::Regular), ([System.Drawing.GraphicsUnit]::Pixel)
    $domainBrush = New-Object System.Drawing.SolidBrush (New-Color '#94A3B8')
    $ds = $g.MeasureString($Domain, $domainFont)
    $g.DrawString($Domain, $domainFont, $domainBrush, ($W - 80 - $ds.Width), ($H - 64))
}

$bmp.Save((Join-Path $imgDir 'og-image.png'), [System.Drawing.Imaging.ImageFormat]::Png)
$g.Dispose(); $bmp.Dispose()

# --- Icons ---
Save-Icon 16 'favicon-16.png' $true
Save-Icon 32 'favicon-32.png' $true
Save-Icon 48 'favicon-48.png' $true
Save-Icon 180 'apple-touch-icon.png' $false
Save-Icon 192 'icon-192.png' $false
Save-Icon 512 'icon-512.png' $false
Copy-Item (Join-Path $imgDir 'icon-512.png') (Join-Path $imgDir 'logo.png') -Force

# --- favicon.ico (PNG-compressed entries, supported by all modern browsers) ---
$entries = @(16, 32, 48) | ForEach-Object { , [System.IO.File]::ReadAllBytes((Join-Path $imgDir "favicon-$_.png")) }
$ms = New-Object System.IO.MemoryStream
$bw = New-Object System.IO.BinaryWriter $ms
$bw.Write([UInt16]0); $bw.Write([UInt16]1); $bw.Write([UInt16]$entries.Count)
$offset = 6 + 16 * $entries.Count
$sizes = @(16, 32, 48)
for ($i = 0; $i -lt $entries.Count; $i++) {
    $bw.Write([byte]$sizes[$i]); $bw.Write([byte]$sizes[$i]); $bw.Write([byte]0); $bw.Write([byte]0)
    $bw.Write([UInt16]1); $bw.Write([UInt16]32)
    $bw.Write([UInt32]$entries[$i].Length); $bw.Write([UInt32]$offset)
    $offset += $entries[$i].Length
}
foreach ($e in $entries) { $bw.Write($e) }
$bw.Flush()
[System.IO.File]::WriteAllBytes((Join-Path $root 'favicon.ico'), $ms.ToArray())
$bw.Dispose()

Remove-Item (Join-Path $imgDir 'favicon-16.png'), (Join-Path $imgDir 'favicon-48.png') -Force

Write-Host "SEO images written to $imgDir and favicon.ico"
