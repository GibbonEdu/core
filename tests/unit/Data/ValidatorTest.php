<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

namespace Gibbon\Data;

use PHPUnit\Framework\TestCase;

/**
 * @covers ValidatorTest
 */
class ValidatorTest extends TestCase
{
    private $validator;

    public function setUp(): void
    {
        $allowableHTML = "br[style],strong[style],b[style],em[style],span[style],p[style],address[style],pre[style|class],h1[style],h2[style],h3[style],h4[style],h5[style],h6[style],table[style],thead[style],tbody[style],tfoot[style],tr[style],td[style|colspan|rowspan],ol[style],ul[style],li[style],blockquote[style],a[style|target|href],img[style|class|src|width|height],video[style],source[style],hr[style],iframe[style|width|height|src|frameborder|allowfullscreen],embed[style],div[class|style],sup[style],sub[style],code[style|class],details[style|class],summary[style|class]";
        $allowableIframes = 'youtube.com';
        
        $this->validator = new Validator($allowableHTML, $allowableIframes);
    }

    public function testCanRemoveDisallowedTags()
    {
        $input = ['value' => '<script>alert(123)</script><SCRIPT SRC=http://xss.test/xss.js></SCRIPT><a onmouseover="alert(document.cookie)">xss link</a>'];
        $expected =  ['value' => 'alert(123)xss link'];

        $this->assertEquals($expected, $this->validator->sanitize($input));
    }

    public function testCanRemoveDisallowedHTMLTags()
    {
        $input = ['value' => '<script>alert(123)</script><SCRIPT SRC=http://xss.test/xss.js></SCRIPT><a onmouseover="alert(document.cookie)">xss link</a>'];
        $expected =  ['value' => 'alert(123)<a>xss link</a>'];

        $this->assertEquals($expected, $this->validator->sanitize($input, ['value' => 'HTML']));
    }

    public function testCanRemoveDisallowedDirective()
    {
        $input = ['value' => "<img src=\"javascript:alert('123')\"><img src=javascript:alert('123')><img src=j&#X41vascript:alert('123')><IMG SRC=javascript:alert(&quot;XSS&quot;)>"];
        $expected =  ['value' => '<img><img><img><img>'];

        $this->assertEquals($expected, $this->validator->sanitize($input, ['value' => 'HTML']));
    }

    public function testCanRemoveDisallowedIframes()
    {
        $input = ['value' => '<iframe src="https://codeception.com">this should be removed</iframe><iframe>but not this</iframe>'];
        $expected =  ['value' => '<iframe>but not this</iframe><!--iFrame removed due to security policy-->'];

        $this->assertEquals($expected, $this->validator->sanitize($input, ['value' => 'HTML']));
    }

    public function testCanKeepAllowedIframes()
    {
        $input = ['value' => '<iframe src="https://www.youtube.com/watch?v=idVB0SEwUfw">this should be allowed</iframe><iframe src="https://codeception.com">this should be removed</iframe><iframe>but not this</iframe>'];
        $expected =  ['value' => '<iframe src="https://www.youtube.com/watch?v=idVB0SEwUfw">this should be allowed</iframe><iframe>but not this</iframe><!--iFrame removed due to security policy-->'];

        $this->assertEquals($expected, $this->validator->sanitize($input, ['value' => 'HTML']));
    }

    public function testCanHandleEscapedTags()
    {
        $input = ['value' => '<IMG """><SCRIPT>alert("XSS")</SCRIPT>"\>'];
        $expected =  ['value' => ''];

        $this->assertEquals($expected, $this->validator->sanitize($input));
    }

    public function testCanHandleGraveAccent()
    {
        $input = ['value' => "<IMG SRC=`javascript:alert(\"'XSS'\")`><a onmouseover=`alert(document.cookie)`>xss link</a><div class='testing' onmouseover=`alert(123)`></div>"];
        $expected =  ['value' => '<img><a>xss link</a><div class="testing"></div>'];

        $this->assertEquals($expected, $this->validator->sanitize($input, ['value' => 'HTML']));
    }

    public function testCanRemoveDisallowedTagsRepeatedly()
    {
        $input = ['value' => '&lt;script&gt;alert(123)&lt;/script&gt;<script>alert(123)</script>'];
        $expected =  ['value' => '&lt;script&gt;alert(123)&lt;/script&gt;alert(123)'];

        $input2 = $this->validator->sanitize($input, ['value' => 'HTML']);

        $this->assertEquals($expected, $this->validator->sanitize($input2, ['value' => 'HTML']));
    }

    public function testCanPassThroughHtmlEntities()
    {
        $input = ['value' => '&lt;script&gt;alert(123)&lt;/script&gt;'];
        $expected =  ['value' => '&lt;script&gt;alert(123)&lt;/script&gt;'];

        $this->assertEquals($expected, $this->validator->sanitize($input, ['value' => 'HTML']));
    }

    public function testCanPassThroughRawValues()
    {
        $input = ['value' => '<script>alert(123)</script>'];
        $expected =  ['value' => '<script>alert(123)</script>'];

        $this->assertEquals($expected, $this->validator->sanitize($input, ['value' => 'Raw']));
    }

    public function testCanSanitizePlainText()
    {
        $input = ['value' => '<div class="testing"><b style="font-style: italic;">Hello</b><script>alert(123)</script></div><IMG """><SCRIPT>alert("XSS")</SCRIPT>"\>'];
        $expected =  ['value' => 'Helloalert(123)'];

        $this->assertEquals($expected, $this->validator->sanitize($input));
    }

    public function testCanSanitizeRichText()
    {
        $input = ['value' => '<div>
            <b>Hello</b><script>alert(123)</script>
            <IMG """><SCRIPT>alert("XSS")</SCRIPT>"\>
        </div>'];
        $expected =  ['value' => '<div>
            <b>Hello</b>alert(123)
            <img>
        </div>'];

        $this->assertEquals($expected, $this->validator->sanitize($input, ['value' => 'HTML']));
    }

    public function testCanSanitizeRichTextAttributes()
    {
        $input = ['value' => '<div onload="alert(123)"><div class="testing" onmouseover="alert(123)">
            <b style="font-style: italic;">Hello</b><script>alert(123)</script>
        </div></div>'];
        $expected =  ['value' => '<div><div class="testing">
            <b style="font-style: italic;">Hello</b>alert(123)
        </div></div>'];

        $this->assertEquals($expected, $this->validator->sanitize($input, ['value' => 'HTML']));
    }

    public function testCanSanitizePlainTextUTF8Chinese()
    {
        $input = ['value' => 'Lorem Ipsum，也称乱数假文或者哑元文本， 是印刷及排版领域所常用的虚拟文字。由于曾经一台匿名的打印机刻意打乱了一盒印刷字体从而造出一本字体样品书，Lorem Ipsum从西元15世纪起就被作为此领域的标准文本使用。<script>alert(123)</script>'];
        $expected =  ['value' => 'Lorem Ipsum，也称乱数假文或者哑元文本， 是印刷及排版领域所常用的虚拟文字。由于曾经一台匿名的打印机刻意打乱了一盒印刷字体从而造出一本字体样品书，Lorem Ipsum从西元15世纪起就被作为此领域的标准文本使用。alert(123)'];

        $this->assertEquals($expected, $this->validator->sanitize($input));
    }

    public function testCanSanitizePlainTextUTF8Thai()
    {
        $input = ['value' => 'Lorem Ipsum คือ เนื้อหาจำลองแบบเรียบๆ ที่ใช้กันในธุรกิจงานพิมพ์หรืองานเรียงพิมพ์ มันได้กลายมาเป็นเนื้อหาจำลองมาตรฐานของธุรกิจดังกล่าวมาตั้งแต่ศตวรรษที่ 16 เมื่อเครื่องพิมพ์โนเนมเครื่องหนึ่งนำรางตัวพิมพ์มาสลับสับตำแหน่งตัวอักษรเพื่อทำหนังสือตัวอย่าง<script>alert(123)</script>'];
        $expected =  ['value' => 'Lorem Ipsum คือ เนื้อหาจำลองแบบเรียบๆ ที่ใช้กันในธุรกิจงานพิมพ์หรืองานเรียงพิมพ์ มันได้กลายมาเป็นเนื้อหาจำลองมาตรฐานของธุรกิจดังกล่าวมาตั้งแต่ศตวรรษที่ 16 เมื่อเครื่องพิมพ์โนเนมเครื่องหนึ่งนำรางตัวพิมพ์มาสลับสับตำแหน่งตัวอักษรเพื่อทำหนังสือตัวอย่างalert(123)'];

        $this->assertEquals($expected, $this->validator->sanitize($input));
    }

    public function testCanSanitizePlainTextUTF8Arabic()
    {
        $input = ['value' => 'هناك حقيقة مثبتة منذ زمن طويل وهي أن المحتوى المقروء لصفحة ما سيلهي القارئ عن التركيز على الشكل الخارجي للنص أو شكل توضع الفقرات في الصفحة التي يقرأها. ولذلك يتم استخدام طريقة لوريم إيبسوم لأنها تعطي توزيعاَ طبيعياَ -إلى حد ما- للأحرف عوضاً عن استخدام "هنا يوجد محتوى نصي، هنا يوجد محتوى نصي" فتجعلها تبدو (أي الأحرف) وكأنها نص مقروء.<script>alert(123)</script>'];
        $expected =  ['value' => 'هناك حقيقة مثبتة منذ زمن طويل وهي أن المحتوى المقروء لصفحة ما سيلهي القارئ عن التركيز على الشكل الخارجي للنص أو شكل توضع الفقرات في الصفحة التي يقرأها. ولذلك يتم استخدام طريقة لوريم إيبسوم لأنها تعطي توزيعاَ طبيعياَ -إلى حد ما- للأحرف عوضاً عن استخدام "هنا يوجد محتوى نصي، هنا يوجد محتوى نصي" فتجعلها تبدو (أي الأحرف) وكأنها نص مقروء.alert(123)'];

        $this->assertEquals($expected, $this->validator->sanitize($input));
    }

    public function testCanSanitizeRichTextUTF8Chinese()
    {
        $input = ['value' => '<div class="testing" onmouseover="alert(123)">Lorem Ipsum，也称乱数假文或者哑元文本， 是印刷及排版领域所常用的虚拟文字。由于曾经一台匿名的打印机刻意打乱了一盒印刷字体从而造出一本字体样品书，Lorem Ipsum从西元15世纪起就被作为此领域的标准文本使用。</div><script>alert(123)</script>'];
        $expected =  ['value' => '<div class="testing">Lorem Ipsum，也称乱数假文或者哑元文本， 是印刷及排版领域所常用的虚拟文字。由于曾经一台匿名的打印机刻意打乱了一盒印刷字体从而造出一本字体样品书，Lorem Ipsum从西元15世纪起就被作为此领域的标准文本使用。</div>alert(123)'];

        $this->assertEquals($expected, $this->validator->sanitize($input, ['value' => 'HTML']));
    }

    public function testCanSanitizeRichTextUTF8Thai()
    {
        $input = ['value' => '<div class="testing" onmouseover="alert(123)">Lorem Ipsum คือ เนื้อหาจำลองแบบเรียบๆ ที่ใช้กันในธุรกิจงานพิมพ์หรืองานเรียงพิมพ์ มันได้กลายมาเป็นเนื้อหาจำลองมาตรฐานของธุรกิจดังกล่าวมาตั้งแต่ศตวรรษที่ 16 เมื่อเครื่องพิมพ์โนเนมเครื่องหนึ่งนำรางตัวพิมพ์มาสลับสับตำแหน่งตัวอักษรเพื่อทำหนังสือตัวอย่าง</div><script>alert(123)</script>'];
        $expected =  ['value' => '<div class="testing">Lorem Ipsum คือ เนื้อหาจำลองแบบเรียบๆ ที่ใช้กันในธุรกิจงานพิมพ์หรืองานเรียงพิมพ์ มันได้กลายมาเป็นเนื้อหาจำลองมาตรฐานของธุรกิจดังกล่าวมาตั้งแต่ศตวรรษที่ 16 เมื่อเครื่องพิมพ์โนเนมเครื่องหนึ่งนำรางตัวพิมพ์มาสลับสับตำแหน่งตัวอักษรเพื่อทำหนังสือตัวอย่าง</div>alert(123)'];

        $this->assertEquals($expected, $this->validator->sanitize($input, ['value' => 'HTML']));
    }

    public function testCanSanitizeRichTextUTF8Arabic()
    {
        $input = ['value' => '<div class="testing" onmouseover="alert(123)">هناك حقيقة مثبتة منذ زمن طويل وهي أن المحتوى المقروء لصفحة ما سيلهي القارئ عن التركيز على الشكل الخارجي للنص أو شكل توضع الفقرات في الصفحة التي يقرأها. ولذلك يتم استخدام طريقة لوريم إيبسوم لأنها تعطي توزيعاَ طبيعياَ -إلى حد ما- للأحرف عوضاً عن استخدام "هنا يوجد محتوى نصي، هنا يوجد محتوى نصي" فتجعلها تبدو (أي الأحرف) وكأنها نص مقروء.</div><script>alert(123)</script>'];
        $expected =  ['value' => '<div class="testing">هناك حقيقة مثبتة منذ زمن طويل وهي أن المحتوى المقروء لصفحة ما سيلهي القارئ عن التركيز على الشكل الخارجي للنص أو شكل توضع الفقرات في الصفحة التي يقرأها. ولذلك يتم استخدام طريقة لوريم إيبسوم لأنها تعطي توزيعاَ طبيعياَ -إلى حد ما- للأحرف عوضاً عن استخدام "هنا يوجد محتوى نصي، هنا يوجد محتوى نصي" فتجعلها تبدو (أي الأحرف) وكأنها نص مقروء.</div>alert(123)'];

        $this->assertEquals($expected, $this->validator->sanitize($input, ['value' => 'HTML']));
    }
    
}
