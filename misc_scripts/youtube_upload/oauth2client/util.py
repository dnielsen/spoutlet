#!/usr/bin/env python
#
# Copyright 2010 Google Inc.
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
#

"""Common utility library."""

__author__ = ['rafek@google.com (Rafe Kaplan)',
              'guido@google.com (Guido van Rossum)',
]
__all__ = [
  'positional',
]

import gflags
import inspect
import logging
import types
import urllib
import urlparse

logging.basicConfig(filename='../logfile',level=logging.DEBUG)

try:
  from urlparse import parse_qsl
except ImportError:
  from cgi import parse_qsl

logger = logging.getLogger(__name__)

FLAGS = gflags.FLAGS

gflags.DEFINE_enum('positional_parameters_enforcement', 'WARNING',
    ['EXCEPTION', 'WARNING', 'IGNORE'],
    'The action when an oauth2client.util.positional declaration is violated.')


def positional(max_positional_args):
  """A decorator to declare that only the first N arguments my be positional.

  This decorator makes it easy to support Python 3 style key-word only
  parameters. For example, in Python 3 it is possible to write:

    def fn(pos1, *, kwonly1=None, kwonly1=None):
      ...

  All named parameters after * must be a keyword:

    fn(10, 'kw1', 'kw2')  # Raises exception.
    fn(10, kwonly1='kw1')  # Ok.

  Example:
    To define a function like above, do:

      @positional(1)
      def fn(pos1, kwonly1=None, kwonly2=None):
        ...

    If no default value is provided to a keyword argument, it becomes a required
    keyword argument:

      @positional(0)
      def fn(required_kw):
        ...

    This must be called with the keyword parameter:

      fn()  # Raises exception.
      fn(10)  # Raises exception.
      fn(required_kw=10)  # Ok.

    When defining instance or class methods always remember to account for
    'self' and 'cls':

      class MyClass(object):

        @positional(2)
        def my_method(self, pos1, kwonly1=None):
          ...

        @classmethod
        @positional(2)
        def my_method(cls, pos1, kwonly1=None):
          ...

  The positional decorator behavior is controlled by the
  --positional_parameters_enforcement flag. The flag may be set to 'EXCEPTION',
  'WARNING' or 'IGNORE' to raise an exception, log a warning, or do nothing,
  respectively, if a declaration is violated.

  Args:
    max_positional_arguments: Maximum number of positional arguments. All
      parameters after the this index must be keyword only.

  Returns:
    A decorator that prevents using arguments after max_positional_args from
    being used as positional parameters.

  Raises:
    TypeError if a key-word only argument is provided as a positional parameter,
    but only if the --positional_parameters_enforcement flag is set to
    'EXCEPTION'.
  """
  def positional_decorator(wrapped):
    def positional_wrapper(*args, **kwargs):
      if len(args) > max_positional_args:
        plural_s = ''
        if max_positional_args != 1:
          plural_s = 's'
        message = '%s() takes at most %d positional argument%s (%d given)' % (
            wrapped.__name__, max_positional_args, plural_s, len(args))
        if FLAGS.positional_parameters_enforcement == 'EXCEPTION':
          raise TypeError(message)
        elif FLAGS.positional_parameters_enforcement == 'WARNING':
          logger.warning(message)
        else: # IGNORE
          pass
      return wrapped(*args, **kwargs)
    return positional_wrapper

  if isinstance(max_positional_args, (int, long)):
    return positional_decorator
  else:
    args, _, _, defaults = inspect.getargspec(max_positional_args)
    return positional(len(args) - len(defaults))(max_positional_args)


def scopes_to_string(scopes):
  """Converts scope value to a string.

  If scopes is a string then it is simply passed through. If scopes is an
  iterable then a string is returned that is all the individual scopes
  concatenated with spaces.

  Args:
    scopes: string or iterable of strings, the scopes.

  Returns:
    The scopes formatted as a single string.
  """
  if isinstance(scopes, types.StringTypes):
    return scopes
  else:
    return ' '.join(scopes)


def dict_to_tuple_key(dictionary):
  """Converts a dictionary to a tuple that can be used as an immutable key.

  The resulting key is always sorted so that logically equivalent dictionaries
  always produce an identical tuple for a key.

  Args:
    dictionary: the dictionary to use as the key.

  Returns:
    A tuple representing the dictionary in it's naturally sorted ordering.
  """
  return tuple(sorted(dictionary.items()))


def _add_query_parameter(url, name, value):
  """Adds a query parameter to a url.

  Replaces the current value if it already exists in the URL.

  Args:
    url: string, url to add the query parameter to.
    name: string, query parameter name.
    value: string, query parameter value.

  Returns:
    Updated query parameter. Does not update the url if value is None.
  """
  if value is None:
    return url
  else:
    parsed = list(urlparse.urlparse(url))
    q = dict(parse_qsl(parsed[4]))
    q[name] = value
    parsed[4] = urllib.urlencode(q)
    return urlparse.urlunparse(parsed)
